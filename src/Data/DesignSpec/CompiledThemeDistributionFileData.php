<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use InvalidArgumentException;
use Spatie\LaravelData\Data;

final class CompiledThemeDistributionFileData extends Data
{
    public function __construct(
        public readonly string $path,
        public readonly string $contents,
        public readonly string $sha256,
        public readonly int $sizeBytes,
        public readonly string $mediaType,
    ) {
        if (! self::safePath($path)
            || $contents === ''
            || $sizeBytes !== strlen($contents)
            || preg_match('/\A[a-f0-9]{64}\z/', $sha256) !== 1
            || ! hash_equals($sha256, hash('sha256', $contents))
            || trim($mediaType) === '') {
            throw new InvalidArgumentException('design_spec.distribution.file_invalid');
        }
    }

    private static function safePath(string $path): bool
    {
        return $path !== ''
            && strlen($path) <= 255
            && ! str_starts_with($path, '/')
            && ! str_contains($path, "\0")
            && ! str_contains($path, '\\')
            && ! str_contains($path, ':')
            && preg_match('/[\x00-\x1F\x7F]/', $path) !== 1
            && preg_match('#(^|/)\.{1,2}(/|$)#', $path) !== 1;
    }
}
