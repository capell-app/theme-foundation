<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecAssetData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $kind,
        public readonly int $bytes,
    ) {}
}
