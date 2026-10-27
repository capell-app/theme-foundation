<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class SectionVariantScopeData extends Data
{
    /** @param list<string> $themeKeys */
    public function __construct(
        public readonly array $themeKeys,
        public readonly bool $hasTrackedUses,
    ) {}
}
