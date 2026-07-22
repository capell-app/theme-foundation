<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class CompiledThemeFileData extends Data
{
    public function __construct(
        public readonly string $path,
        public readonly string $mediaType,
        public readonly int $sizeBytes,
        public readonly string $sha256,
        public readonly string $contents,
    ) {}
}
