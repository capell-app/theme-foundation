<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\DesignSpec\BuildCompiledThemeDistributionAction;
use Capell\FoundationTheme\Actions\DesignSpec\CompileFoundationThemeArtifactAction;
use Capell\FoundationTheme\Actions\DesignSpec\ValidateCompiledThemeDistributionAction;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeDistributionData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeDistributionFileData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeDistributionReceiptData;
use Capell\FoundationTheme\Tests\Fixtures\DesignSpec\Ed25519CompiledThemeReceiptSigningAuthority;
use ZipStream\CompressionMethod;
use ZipStream\ZipStream;

/** @return array{CompiledThemeDistributionData, string, ValidateCompiledThemeDistributionAction} */
function compiledThemeDistributionFixture(): array
{
    $sourceBytes = CompileFoundationThemeArtifactAction::run(
        compiledThemeDistributionDesignSpecFixture(),
    )->artifactBytes;
    $authority = new Ed25519CompiledThemeReceiptSigningAuthority;
    $validator = new ValidateCompiledThemeDistributionAction($authority);
    $builder = new BuildCompiledThemeDistributionAction($authority, $validator);
    $distribution = $builder->handle(
        $sourceBytes,
        new DateTimeImmutable('2026-07-21T12:00:00Z'),
        new DateTimeImmutable('2026-07-22T12:00:00Z'),
    );

    return [$distribution, $sourceBytes, $validator];
}

function compiledThemeDistributionDesignSpecFixture(): string
{
    $contents = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/design-spec/v1-canonical.json');

    return $contents === false ? throw new RuntimeException('Unable to load distribution DesignSpec fixture.') : $contents;
}

it('builds a deterministic signed archive bound to every released identity', function (): void {
    [$first, $sourceBytes, $validator] = compiledThemeDistributionFixture();
    [$second] = compiledThemeDistributionFixture();

    expect($first->archiveBytes)->toBe($second->archiveBytes)
        ->and($first->archiveSha256)->toBe($second->archiveSha256)
        ->and($first->receipt->signature)->toBe($second->receipt->signature)
        ->and($first->receipt->canonicalPayloadBytes())->toBe($second->receipt->canonicalPayloadBytes())
        ->and(array_keys($first->receipt->bindings))->toBe([
            'archive_sha256',
            'compiler_identity_sha256',
            'design_spec_sha256',
            'file_manifest_sha256',
            'release_identity_sha256',
            'source_artifact_sha256',
            'template_identity_sha256',
        ])
        ->and(array_column($first->files, 'path'))->toBe(array_keys(CompileFoundationThemeArtifactAction::FILE_MEDIA_TYPES));

    $validator->handle($first, $sourceBytes);
});

it('rejects archive bytes that differ from the canonical declared file map', function (): void {
    [$distribution, $sourceBytes, $validator] = compiledThemeDistributionFixture();
    $archiveBytes = distributionZip([
        ...array_map(
            static fn (CompiledThemeDistributionFileData $file): array => [$file->path, $file->contents],
            $distribution->files,
        ),
        [$distribution->files[0]->path, 'replacement'],
    ]);
    $altered = distributionWith($distribution, archiveBytes: $archiveBytes);

    expect(fn (): null => $validator->handle($altered, $sourceBytes))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects duplicate declared paths including case-folded collisions', function (): void {
    [$distribution, $sourceBytes, $validator] = compiledThemeDistributionFixture();
    $files = [...$distribution->files, $distribution->files[0]];
    $altered = distributionWith($distribution, files: $files);

    expect(fn (): null => $validator->handle($altered, $sourceBytes))
        ->toThrow(InvalidArgumentException::class, 'file_map_invalid');

    $first = $distribution->files[0];
    $caseCollision = new CompiledThemeDistributionFileData(
        strtoupper($first->path),
        $first->contents,
        $first->sha256,
        $first->sizeBytes,
        $first->mediaType,
    );
    $caseFoldedFiles = [...$distribution->files, $caseCollision];
    $caseFolded = distributionWith($distribution, files: $caseFoldedFiles);

    expect(fn (): null => $validator->handle($caseFolded, $sourceBytes))
        ->toThrow(InvalidArgumentException::class, 'file_map_invalid');
});

it('rejects unsafe paths and false size or digest evidence', function (): void {
    expect(fn (): CompiledThemeDistributionFileData => new CompiledThemeDistributionFileData(
        '../theme.php',
        'contents',
        hash('sha256', 'contents'),
        strlen('contents'),
        'application/x-httpd-php',
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): CompiledThemeDistributionFileData => new CompiledThemeDistributionFileData(
        'theme.php',
        'contents',
        hash('sha256', 'contents'),
        1,
        'application/x-httpd-php',
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): CompiledThemeDistributionFileData => new CompiledThemeDistributionFileData(
        'theme.php',
        'contents',
        str_repeat('0', 64),
        strlen('contents'),
        'application/x-httpd-php',
    ))->toThrow(InvalidArgumentException::class);
});

it('rejects a receipt signature not issued by the injected trusted authority', function (): void {
    [$distribution, $sourceBytes, $validator] = compiledThemeDistributionFixture();
    $receipt = new CompiledThemeDistributionReceiptData(
        schemaVersion: $distribution->receipt->schemaVersion,
        issuer: $distribution->receipt->issuer,
        keyId: $distribution->receipt->keyId,
        artifactType: $distribution->receipt->artifactType,
        subject: $distribution->receipt->subject,
        bindings: $distribution->receipt->bindings,
        issuedAt: $distribution->receipt->issuedAt,
        expiresAt: $distribution->receipt->expiresAt,
        signature: base64_encode(str_repeat("\x01", SODIUM_CRYPTO_SIGN_BYTES)),
    );
    $altered = distributionWith($distribution, receipt: $receipt);

    expect(fn (): null => $validator->handle($altered, $sourceBytes))
        ->toThrow(InvalidArgumentException::class, 'signature_invalid');
});

it('rejects a caller-asserted package release identity', function (): void {
    [$distribution, $sourceBytes, $validator] = compiledThemeDistributionFixture();
    $altered = distributionWith($distribution, releaseIdentity: 'capell-app/theme-foundation@unreleased');

    expect(fn (): null => $validator->handle($altered, $sourceBytes))
        ->toThrow(InvalidArgumentException::class, 'source_identity_invalid');
});

/**
 * @param  list<array{string, string}>  $entries
 */
function distributionZip(array $entries): string
{
    $stream = fopen('php://temp', 'w+b');
    if ($stream === false) {
        throw new RuntimeException('Unable to create distribution test stream.');
    }

    $archive = new ZipStream(
        outputStream: $stream,
        defaultCompressionMethod: CompressionMethod::STORE,
        enableZip64: false,
        defaultEnableZeroHeader: false,
        sendHttpHeaders: false,
    );
    foreach ($entries as [$name, $contents]) {
        $archive->addFile(
            fileName: $name,
            data: $contents,
            lastModificationDateTime: new DateTimeImmutable('2000-01-01T00:00:00Z'),
        );
    }
    $archive->finish();
    rewind($stream);
    $bytes = stream_get_contents($stream);
    fclose($stream);

    return is_string($bytes) ? $bytes : throw new RuntimeException('Unable to read distribution test archive.');
}

/**
 * @param  list<CompiledThemeDistributionFileData>|null  $files
 */
function distributionWith(
    CompiledThemeDistributionData $distribution,
    ?string $archiveBytes = null,
    ?array $files = null,
    ?CompiledThemeDistributionReceiptData $receipt = null,
    ?string $releaseIdentity = null,
): CompiledThemeDistributionData {
    $archiveBytes ??= $distribution->archiveBytes;
    $files ??= $distribution->files;

    return new CompiledThemeDistributionData(
        sourceArtifactSha256: $distribution->sourceArtifactSha256,
        designSpecSha256: $distribution->designSpecSha256,
        compilerIdentity: $distribution->compilerIdentity,
        templateIdentity: $distribution->templateIdentity,
        releaseIdentity: $releaseIdentity ?? $distribution->releaseIdentity,
        archiveBytes: $archiveBytes,
        archiveSha256: hash('sha256', $archiveBytes),
        archiveSizeBytes: strlen($archiveBytes),
        archiveMediaType: $distribution->archiveMediaType,
        fileManifestSha256: CompiledThemeDistributionData::digestFileManifest($files),
        files: $files,
        receipt: $receipt ?? $distribution->receipt,
    );
}
