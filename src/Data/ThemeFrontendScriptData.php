<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class ThemeFrontendScriptData extends Data
{
    public function __construct(
        public readonly string $themeKey,
        public readonly string $handle,
        public readonly string $packageName,
        public readonly string $entry,
        public readonly string $publicDirectory,
    ) {}
}
