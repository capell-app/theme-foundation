<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecPaletteData extends Data
{
    public function __construct(
        public readonly DesignSpecColorModeData $light,
        public readonly DesignSpecColorModeData $dark,
    ) {}
}
