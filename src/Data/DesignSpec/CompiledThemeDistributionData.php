<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use InvalidArgumentException;
use JsonException;
use Spatie\LaravelData\Data;

final class CompiledThemeDistributionData extends Data
{
    /** @param list<CompiledThemeDistributionFileData> $files */
    public function __construct(
        public readonly string $sourceArtifactSha256,
        public readonly string $designSpecSha256,
        public readonly string $compilerIdentity,
        public readonly string $templateIdentity,
        public readonly string $releaseIdentity,
        public readonly string $archiveBytes,
        public readonly string $archiveSha256,
        public readonly int $archiveSizeBytes,
        public readonly string $archiveMediaType,
        public readonly string $fileManifestSha256,
        public readonly array $files,
        public readonly CompiledThemeDistributionReceiptData $receipt,
    ) {
        if (preg_match('/\A[a-f0-9]{64}\z/', $sourceArtifactSha256) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/', $designSpecSha256) !== 1
            || trim($compilerIdentity) === ''
            || trim($templateIdentity) === ''
            || trim($releaseIdentity) === ''
            || $archiveBytes === ''
            || $archiveSizeBytes !== strlen($archiveBytes)
            || ! hash_equals($archiveSha256, hash('sha256', $archiveBytes))
            || $archiveMediaType !== 'application/zip'
            || $files === []
            || ! hash_equals($fileManifestSha256, self::digestFileManifest($files))) {
            throw new InvalidArgumentException('design_spec.distribution.invalid');
        }
    }

    /** @param list<CompiledThemeDistributionFileData> $files */
    public static function digestFileManifest(array $files): string
    {
        try {
            $bytes = json_encode(array_map(
                static fn (CompiledThemeDistributionFileData $file): array => [
                    'media_type' => $file->mediaType,
                    'path' => $file->path,
                    'sha256' => $file->sha256,
                    'size_bytes' => $file->sizeBytes,
                ],
                $files,
            ), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            throw new InvalidArgumentException('design_spec.distribution.file_manifest_encoding_failed');
        }

        return hash('sha256', $bytes);
    }
}
