<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecSiteData extends Data
{
    /**
     * @param  list<string>  $localeCodes
     * @param  list<string>  $fallbackLocaleCodes
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly array $localeCodes,
        public readonly string $defaultLocale,
        public readonly array $fallbackLocaleCodes,
    ) {}
}
