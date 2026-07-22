<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class CanonicalDesignSpecData extends Data
{
    public function __construct(
        public readonly int $schemaVersion,
        public readonly string $bytes,
        public readonly string $sha256,
        public readonly DesignSpecData $specification,
    ) {}
}
