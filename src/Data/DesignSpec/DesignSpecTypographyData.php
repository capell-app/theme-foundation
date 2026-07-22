<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecTypographyData extends Data
{
    /** @param list<DesignSpecLocaleTypographyData> $locales */
    public function __construct(public readonly array $locales) {}
}
