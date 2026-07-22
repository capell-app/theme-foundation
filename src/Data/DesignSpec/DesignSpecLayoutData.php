<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecLayoutData extends Data
{
    public function __construct(
        public readonly string $container,
        public readonly string $density,
        public readonly string $spacing,
        public readonly string $radius,
        public readonly string $darkMode,
    ) {}
}
