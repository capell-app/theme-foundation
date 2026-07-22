<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class CompiledThemeArtifactData extends Data
{
    /** @param list<CompiledThemeFileData> $files */
    public function __construct(
        public readonly string $artifactType,
        public readonly int $schemaVersion,
        public readonly string $compilerVersion,
        public readonly string $templateVersion,
        public readonly string $designSpecSha256,
        public readonly string $contentSha256,
        public readonly string $artifactBytes,
        public readonly string $artifactSha256,
        public readonly array $files,
    ) {}
}
