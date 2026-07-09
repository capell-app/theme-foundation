<?php

use Capell\FoundationTheme\Actions\ResolveFoundationThemeTokensAction;
use Capell\FoundationTheme\Data\FoundationThemeTokensData;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\Frontend\Facades\Frontend;

$preparedTokens = Frontend::getFrontendData('foundation.theme.tokens');
$tokens = $preparedTokens instanceof FoundationThemeTokensData
    ? $preparedTokens
    : ResolveFoundationThemeTokensAction::run(resolveSettings: false);

// prefers-reduced-motion always wins: force the "none" tier's static
// composition regardless of the theme's configured motionIntensity token.
$noneMotionTokens = FoundationThemeSettings::MOTION_INTENSITY_OPTIONS['none'];

?>

<style>
    :root {
        @foreach ($tokens->paletteColors as $paletteColor)
        --color-{{ $paletteColor['name'] }}: {{ $paletteColor['value'] }};
        @endforeach
        --color-brand: {{ $tokens->brandColor }};
        --color-link: {{ $tokens->linkColor }};
        --color-link-active: {{ $tokens->linkColorActive }};
        --color-divider: {{ $tokens->dividerColor }};
        --foundation-page-bg: {{ $tokens->pageBackground }};
        --foundation-body-fg: color-mix(in srgb, var(--color-brand) 82%, #111827);
        --foundation-surface-bg: {{ $tokens->surfaceBackground }};
        --foundation-muted-bg: {{ $tokens->mutedBackground }};
        --foundation-header-bg: {{ $tokens->headerBackground }};
        --foundation-border: {{ $tokens->borderColor }};
        --foundation-border-strong: {{ $tokens->borderStrongColor }};
        --foundation-card-bg: {{ $tokens->cardBackground }};
        --foundation-primary-action: {{ $tokens->primaryAction }};
        --foundation-band-bg: {{ $tokens->bandBackground }};
        --foundation-band-alt-bg: {{ $tokens->bandAlternateBackground }};
        --foundation-band-accent-bg: {{ $tokens->bandAccentBackground }};
        --foundation-band-border: {{ $tokens->bandBorder }};
        --foundation-image-border: {{ $tokens->imageBorder }};
        --foundation-image-radius: {{ $tokens->imageRadius }};
        --foundation-section-spacing: {{ $tokens->sectionSpacing }};
        --foundation-widget-gap: {{ $tokens->widgetGap }};
        --foundation-heading-size-h1: {{ $tokens->headingSizeH1 }};
        --foundation-heading-size-h2: {{ $tokens->headingSizeH2 }};
        --foundation-heading-size-h3: {{ $tokens->headingSizeH3 }};
        --foundation-heading-line-height: {{ $tokens->headingLineHeight }};
        --foundation-radius: 0.5rem;
        --foundation-motion-duration: {{ $tokens->motionDuration }};
        --foundation-motion-ease: {{ $tokens->motionEase }};
        --foundation-motion-stagger: {{ $tokens->motionStagger }};
        --foundation-motion-distance: {{ $tokens->motionDistance }};
    }

    .dark:root {
        --foundation-page-bg: {{ $tokens->darkPageBackground }};
        --foundation-body-fg: #f8fafc;
        --foundation-surface-bg: {{ $tokens->darkSurfaceBackground }};
        --foundation-muted-bg: {{ $tokens->darkMutedBackground }};
        --foundation-header-bg: {{ $tokens->darkHeaderBackground }};
        --foundation-border: {{ $tokens->darkBorderColor }};
        --foundation-border-strong: {{ $tokens->darkBorderStrongColor }};
        --foundation-card-bg: {{ $tokens->darkCardBackground }};
        --foundation-primary-action: {{ $tokens->darkPrimaryAction }};
        --foundation-band-bg: {{ $tokens->darkBandBackground }};
        --foundation-band-alt-bg: {{ $tokens->darkBandAlternateBackground }};
        --foundation-band-accent-bg: {{ $tokens->darkBandAccentBackground }};
        --foundation-band-border: {{ $tokens->darkBandBorder }};
        --foundation-image-border: {{ $tokens->darkImageBorder }};
    }
    @media (prefers-reduced-motion: reduce) {
        :root {
            --foundation-motion-duration: {{ $noneMotionTokens['duration'] }};
            --foundation-motion-ease: {{ $noneMotionTokens['ease'] }};
            --foundation-motion-stagger: {{ $noneMotionTokens['stagger'] }};
            --foundation-motion-distance: {{ $noneMotionTokens['distance'] }};
        }
    }
</style>
