<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecComponentsData extends Data
{
    public function __construct(
        public readonly string $button,
        public readonly string $card,
        public readonly string $navigation,
        public readonly string $hero,
    ) {}
}
