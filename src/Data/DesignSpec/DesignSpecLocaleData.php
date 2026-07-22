<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecLocaleData extends Data
{
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly string $direction,
    ) {}
}
