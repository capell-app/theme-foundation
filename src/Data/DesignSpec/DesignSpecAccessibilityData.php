<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecAccessibilityData extends Data
{
    public function __construct(
        public readonly string $reducedMotion,
        public readonly string $focusIndicator,
        public readonly string $landmarks,
        public readonly string $headingHierarchy,
    ) {}
}
