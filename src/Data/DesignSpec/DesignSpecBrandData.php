<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecBrandData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $logoAssetId,
    ) {}
}
