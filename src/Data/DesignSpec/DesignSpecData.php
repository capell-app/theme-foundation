<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data\DesignSpec;

use Spatie\LaravelData\Data;

final class DesignSpecData extends Data
{
    /**
     * @param  list<DesignSpecSiteData>  $sites
     * @param  list<DesignSpecLocaleData>  $locales
     * @param  list<DesignSpecAssetData>  $assets
     */
    public function __construct(
        public readonly int $schemaVersion,
        public readonly string $template,
        public readonly DesignSpecDisplayData $display,
        public readonly array $sites,
        public readonly array $locales,
        public readonly DesignSpecBrandData $brand,
        public readonly DesignSpecPaletteData $palette,
        public readonly DesignSpecTypographyData $typography,
        public readonly DesignSpecLayoutData $layout,
        public readonly DesignSpecComponentsData $components,
        public readonly DesignSpecAccessibilityData $accessibility,
        public readonly array $assets,
    ) {}
}
