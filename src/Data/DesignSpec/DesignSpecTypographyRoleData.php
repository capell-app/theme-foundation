<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecTypographyRoleData extends Data
{
    public function __construct(
        public readonly string $family,
        public readonly string $style,
        public readonly int $weight,
        public readonly ?string $fontAssetId,
    ) {}
}
