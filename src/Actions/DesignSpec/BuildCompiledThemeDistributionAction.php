<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\DesignSpec;

use Capell\FoundationTheme\Contracts\CompiledThemeReceiptSigningAuthority;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeDistributionData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeDistributionFileData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeDistributionReceiptData;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

final readonly class BuildCompiledThemeDistributionAction
{
    public function __construct(
        private CompiledThemeReceiptSigningAuthority $signingAuthority,
        private ValidateCompiledThemeDistributionAction $validateDistribution,
    ) {}

    public function handle(
        string $compiledArtifactBytes,
        DateTimeImmutable $issuedAt,
        DateTimeImmutable $expiresAt,
    ): CompiledThemeDistributionData {
        $releaseIdentity = trim($this->signingAuthority->releaseIdentity());
        $notAfter = $this->signingAuthority->notAfter();
        if ($releaseIdentity === ''
            || $expiresAt <= $issuedAt
            || $issuedAt < $this->signingAuthority->notBefore()
            || ($notAfter instanceof DateTimeImmutable && $expiresAt > $notAfter)) {
            throw new InvalidArgumentException('design_spec.distribution.release_invalid');
        }

        $artifact = ValidateCompiledThemeArtifactAction::run($compiledArtifactBytes);
        $files = [];
        foreach ($artifact->files as $file) {
            $files[] = new CompiledThemeDistributionFileData(
                path: $file->path,
                contents: $file->contents,
                sha256: $file->sha256,
                sizeBytes: $file->sizeBytes,
                mediaType: $file->mediaType,
            );
        }
        usort($files, static fn (CompiledThemeDistributionFileData $left, CompiledThemeDistributionFileData $right): int => strcmp($left->path, $right->path));

        $archiveBytes = $this->archive($files);
        $archiveSha256 = hash('sha256', $archiveBytes);
        $fileManifestSha256 = CompiledThemeDistributionData::digestFileManifest($files);
        $bindings = ValidateCompiledThemeDistributionAction::bindings(
            archiveSha256: $archiveSha256,
            fileManifestSha256: $fileManifestSha256,
            sourceArtifactSha256: $artifact->artifactSha256,
            designSpecSha256: $artifact->designSpecSha256,
            compilerIdentity: $artifact->compilerVersion,
            templateIdentity: $artifact->templateVersion,
            releaseIdentity: $releaseIdentity,
        );
        $issuer = $this->signingAuthority->issuer();
        $keyId = $this->signingAuthority->keyId();
        $signingMessage = CompiledThemeDistributionReceiptData::signingMessageFor(
            $issuer,
            $keyId,
            'compiled-theme-distribution',
            $artifact->artifactSha256,
            $bindings,
            $issuedAt,
            $expiresAt,
        );
        $receipt = new CompiledThemeDistributionReceiptData(
            schemaVersion: 1,
            issuer: $issuer,
            keyId: $keyId,
            artifactType: 'compiled-theme-distribution',
            subject: $artifact->artifactSha256,
            bindings: $bindings,
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            signature: $this->signingAuthority->sign($signingMessage),
        );
        $distribution = new CompiledThemeDistributionData(
            sourceArtifactSha256: $artifact->artifactSha256,
            designSpecSha256: $artifact->designSpecSha256,
            compilerIdentity: $artifact->compilerVersion,
            templateIdentity: $artifact->templateVersion,
            releaseIdentity: trim($releaseIdentity),
            archiveBytes: $archiveBytes,
            archiveSha256: $archiveSha256,
            archiveSizeBytes: strlen($archiveBytes),
            archiveMediaType: 'application/zip',
            fileManifestSha256: $fileManifestSha256,
            files: $files,
            receipt: $receipt,
        );
        $this->validateDistribution->handle($distribution, $compiledArtifactBytes);

        return $distribution;
    }

    /** @param list<CompiledThemeDistributionFileData> $files */
    private function archive(array $files): string
    {
        $temporaryFile = tmpfile();
        if ($temporaryFile === false) {
            throw new RuntimeException('design_spec.distribution.archive_temporary_file_failed');
        }

        $metadata = stream_get_meta_data($temporaryFile);
        $path = $metadata['uri'] ?? null;
        if (! is_string($path) || $path === '') {
            fclose($temporaryFile);

            throw new RuntimeException('design_spec.distribution.archive_temporary_file_failed');
        }

        $archive = new ZipArchive;
        $archiveOpen = false;
        try {
            if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('design_spec.distribution.archive_open_failed');
            }
            $archiveOpen = true;
            foreach ($files as $file) {
                if (! $archive->addFromString($file->path, $file->contents)
                    || ! $archive->setCompressionName($file->path, ZipArchive::CM_STORE)
                    || ! $archive->setMtimeName($file->path, 946684800)) {
                    throw new RuntimeException('design_spec.distribution.archive_write_failed');
                }
            }
            $closed = $archive->close();
            $archiveOpen = false;
            if (! $closed) {
                throw new RuntimeException('design_spec.distribution.archive_close_failed');
            }

            $bytes = file_get_contents($path);

            return is_string($bytes) && $bytes !== ''
                ? $bytes
                : throw new RuntimeException('design_spec.distribution.archive_read_failed');
        } finally {
            if ($archiveOpen) {
                $archive->close();
            }
            fclose($temporaryFile);
        }
    }
}
