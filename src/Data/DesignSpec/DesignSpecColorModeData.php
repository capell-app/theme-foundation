<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecColorModeData extends Data
{
    public function __construct(
        public readonly string $background,
        public readonly string $surface,
        public readonly string $text,
        public readonly string $mutedText,
        public readonly string $largeText,
        public readonly string $primary,
        public readonly string $onPrimary,
        public readonly string $border,
        public readonly string $focus,
    ) {}
}
