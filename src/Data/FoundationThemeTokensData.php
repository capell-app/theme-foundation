<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

final readonly class FoundationThemeTokensData
{
    /**
     * @param  array<int, array{name: string, value: string}>  $paletteColors
     */
    public function __construct(
        public array $paletteColors,
        public string $brandColor,
        public string $linkColor,
        public string $linkColorActive,
        public string $dividerColor,
        public string $pageBackground,
        public string $surfaceBackground,
        public string $mutedBackground,
        public string $headerBackground,
        public string $borderColor,
        public string $borderStrongColor,
        public string $cardBackground,
        public string $primaryAction,
        public string $bandBackground,
        public string $bandAlternateBackground,
        public string $bandAccentBackground,
        public string $bandBorder,
        public string $imageBorder,
        public string $darkPageBackground,
        public string $darkSurfaceBackground,
        public string $darkMutedBackground,
        public string $darkHeaderBackground,
        public string $darkBorderColor,
        public string $darkBorderStrongColor,
        public string $darkCardBackground,
        public string $darkPrimaryAction,
        public string $darkBandBackground,
        public string $darkBandAlternateBackground,
        public string $darkBandAccentBackground,
        public string $darkBandBorder,
        public string $darkImageBorder,
        public string $imageRadius,
        public string $sectionSpacing,
        public string $widgetGap,
        public string $headingSizeH1,
        public string $headingSizeH2,
        public string $headingSizeH3,
        public string $headingLineHeight,
    ) {}
}
