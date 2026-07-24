<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\DesignSpec;

use Capell\FoundationTheme\Contracts\CompiledThemeReceiptSigningAuthority;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeDistributionData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeDistributionFileData;
use Capell\FoundationTheme\Data\DesignSpec\CompiledThemeFileData;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

final readonly class ValidateCompiledThemeDistributionAction
{
    public function __construct(private CompiledThemeReceiptSigningAuthority $signingAuthority) {}

    /**
     * @return array<string, string>
     */
    public static function bindings(
        string $archiveSha256,
        string $fileManifestSha256,
        string $sourceArtifactSha256,
        string $designSpecSha256,
        string $compilerIdentity,
        string $templateIdentity,
        string $releaseIdentity,
    ): array {
        return [
            'archive_sha256' => $archiveSha256,
            'compiler_identity_sha256' => hash('sha256', $compilerIdentity),
            'design_spec_sha256' => $designSpecSha256,
            'file_manifest_sha256' => $fileManifestSha256,
            'release_identity_sha256' => hash('sha256', $releaseIdentity),
            'source_artifact_sha256' => $sourceArtifactSha256,
            'template_identity_sha256' => hash('sha256', $templateIdentity),
        ];
    }

    public function handle(CompiledThemeDistributionData $distribution, string $sourceArtifactBytes): void
    {
        $validatedArtifact = ValidateCompiledThemeArtifactAction::run($sourceArtifactBytes);
        if (! hash_equals($distribution->sourceArtifactSha256, $validatedArtifact->artifactSha256)
            || ! hash_equals($distribution->designSpecSha256, $validatedArtifact->designSpecSha256)
            || $distribution->compilerIdentity !== $validatedArtifact->compilerVersion
            || $distribution->templateIdentity !== $validatedArtifact->templateVersion
            || $distribution->releaseIdentity !== trim($this->signingAuthority->releaseIdentity())) {
            throw new InvalidArgumentException('design_spec.distribution.source_identity_invalid');
        }

        $this->assertFileMap($distribution->files);
        $this->assertSourceFileMap($validatedArtifact->files, $distribution->files);
        $this->assertArchive($distribution);
        $expectedBindings = self::bindings(
            archiveSha256: $distribution->archiveSha256,
            fileManifestSha256: $distribution->fileManifestSha256,
            sourceArtifactSha256: $distribution->sourceArtifactSha256,
            designSpecSha256: $distribution->designSpecSha256,
            compilerIdentity: $distribution->compilerIdentity,
            templateIdentity: $distribution->templateIdentity,
            releaseIdentity: $distribution->releaseIdentity,
        );
        $receipt = $distribution->receipt;
        $notAfter = $this->signingAuthority->notAfter();
        if ($receipt->issuer !== $this->signingAuthority->issuer()
            || $receipt->keyId !== $this->signingAuthority->keyId()
            || $receipt->artifactType !== 'compiled-theme-distribution'
            || ! hash_equals($receipt->subject, $distribution->sourceArtifactSha256)
            || $receipt->bindings !== $expectedBindings
            || $receipt->issuedAt < $this->signingAuthority->notBefore()
            || ($notAfter instanceof DateTimeImmutable && $receipt->expiresAt > $notAfter)
            || ! $this->signingAuthority->verify($receipt->signingMessage(), $receipt->signature)) {
            throw new InvalidArgumentException('design_spec.distribution.signature_invalid');
        }
    }

    /** @param list<CompiledThemeDistributionFileData> $files */
    private function assertFileMap(array $files): void
    {
        $paths = [];
        $caseFolded = [];
        foreach ($files as $file) {
            if (! $file instanceof CompiledThemeDistributionFileData
                || isset($paths[$file->path])
                || isset($caseFolded[strtolower($file->path)])) {
                throw new InvalidArgumentException('design_spec.distribution.file_map_invalid');
            }

            $paths[$file->path] = true;
            $caseFolded[strtolower($file->path)] = true;
        }

        $actual = array_keys($paths);
        $sorted = $actual;
        sort($sorted, SORT_STRING);
        if ($actual !== $sorted) {
            throw new InvalidArgumentException('design_spec.distribution.file_order_invalid');
        }
    }

    /**
     * @param  list<CompiledThemeFileData>  $sourceFiles
     * @param  list<CompiledThemeDistributionFileData>  $distributionFiles
     */
    private function assertSourceFileMap(array $sourceFiles, array $distributionFiles): void
    {
        if (count($sourceFiles) !== count($distributionFiles)) {
            throw new InvalidArgumentException('design_spec.distribution.source_file_map_invalid');
        }

        foreach ($sourceFiles as $index => $sourceFile) {
            $distributionFile = $distributionFiles[$index] ?? null;
            if (! $distributionFile instanceof CompiledThemeDistributionFileData
                || $distributionFile->path !== $sourceFile->path
                || $distributionFile->mediaType !== $sourceFile->mediaType
                || $distributionFile->sizeBytes !== $sourceFile->sizeBytes
                || ! hash_equals($distributionFile->sha256, $sourceFile->sha256)
                || ! hash_equals($distributionFile->contents, $sourceFile->contents)) {
                throw new InvalidArgumentException('design_spec.distribution.source_file_map_invalid');
            }
        }
    }

    private function assertArchive(CompiledThemeDistributionData $distribution): void
    {
        $temporaryFile = tmpfile();
        if ($temporaryFile === false) {
            throw new RuntimeException('design_spec.distribution.archive_temporary_file_failed');
        }

        $metadata = stream_get_meta_data($temporaryFile);
        $path = $metadata['uri'] ?? null;
        if (! is_string($path)
            || $path === ''
            || fwrite($temporaryFile, $distribution->archiveBytes) !== strlen($distribution->archiveBytes)
            || ! fflush($temporaryFile)) {
            fclose($temporaryFile);

            throw new RuntimeException('design_spec.distribution.archive_temporary_file_failed');
        }

        $archive = new ZipArchive;
        $opened = false;
        try {
            $opened = $archive->open($path, ZipArchive::RDONLY) === true;
            if (! $opened || $archive->numFiles !== count($distribution->files)) {
                throw new InvalidArgumentException('design_spec.distribution.archive_invalid');
            }

            $declared = [];
            foreach ($distribution->files as $file) {
                $declared[$file->path] = $file;
            }
            $seen = [];
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $name = $archive->getNameIndex($index, ZipArchive::FL_UNCHANGED);
                $stat = $archive->statIndex($index, ZipArchive::FL_UNCHANGED);
                if (! is_string($name)
                    || ! is_array($stat)
                    || isset($seen[$name])
                    || ! isset($declared[$name])
                    || ($stat['size'] ?? null) !== $declared[$name]->sizeBytes) {
                    throw new InvalidArgumentException('design_spec.distribution.archive_file_map_invalid');
                }

                $contents = $archive->getFromIndex($index);
                if (! is_string($contents)
                    || ! hash_equals($declared[$name]->contents, $contents)
                    || ! hash_equals($declared[$name]->sha256, hash('sha256', $contents))) {
                    throw new InvalidArgumentException('design_spec.distribution.archive_content_invalid');
                }
                $seen[$name] = true;
            }

            if (array_keys($seen) !== array_keys($declared)) {
                throw new InvalidArgumentException('design_spec.distribution.archive_file_map_invalid');
            }
        } finally {
            if ($opened) {
                $archive->close();
            }
            fclose($temporaryFile);
        }
    }
}
