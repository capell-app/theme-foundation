<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecLocaleTypographyData extends Data
{
    public function __construct(
        public readonly string $locale,
        public readonly DesignSpecTypographyRoleData $heading,
        public readonly DesignSpecTypographyRoleData $body,
    ) {}
}
