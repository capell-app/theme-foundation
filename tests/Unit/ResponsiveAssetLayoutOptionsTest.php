<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\ResponsiveAssetLayoutOptions;
use Capell\LayoutBuilder\Enums\ResponsiveLayoutPattern;
use Capell\LayoutBuilder\Models\Widget;

it('resolves configurable grid and carousel layout options from widget meta', function (): void {
    $widget = new Widget([
        'meta' => [
            'responsive_layout_pattern' => ResponsiveLayoutPattern::DesktopGridMobileCarousel->value,
            'responsive_grid_sm_columns' => 2,
            'responsive_grid_md_columns' => 4,
            'responsive_grid_rows' => 2,
            'responsive_carousel_mobile_slides' => '1.25',
            'responsive_carousel_sm_slides' => '2',
            'responsive_carousel_rows' => 2,
            'responsive_carousel_highlight_active' => true,
            'carousel_loop' => true,
        ],
    ]);

    $options = ResponsiveAssetLayoutOptions::fromWidget($widget, 9);

    expect($options->pattern)->toBe(ResponsiveLayoutPattern::DesktopGridMobileCarousel)
        ->and($options->smColumns)->toBe(2)
        ->and($options->mdColumns)->toBe(4)
        ->and($options->gridRows)->toBe(2)
        ->and($options->mobileSlides)->toBe(1.25)
        ->and($options->smSlides)->toBe(2.0)
        ->and($options->carouselRows)->toBe(2)
        ->and($options->carouselAlign())->toBe('center')
        ->and($options->carouselLoop())->toBeFalse()
        ->and($options->carouselBreakpointsJson())->toContain('"slidesPerView":1.25')
        ->and($options->carouselBreakpointsJson())->toContain('"slidesPerView":3')
        ->and((string) $options->gridRowsStyle('test-grid'))->toContain('#test-grid > :nth-child(n + 9)');
});

it('defaults repeated widget assets to a desktop grid with a mobile carousel', function (): void {
    $widget = new Widget(['meta' => []]);

    $options = ResponsiveAssetLayoutOptions::fromWidget($widget, 4);

    expect($options->pattern)->toBe(ResponsiveLayoutPattern::DesktopGridMobileCarousel)
        ->and($options->pattern->usesMobileCarousel())->toBeTrue()
        ->and($options->pattern->usesDesktopGrid())->toBeTrue()
        ->and($options->carouselBreakpointsJson())->toContain('"320"')
        ->and($options->carouselBreakpointsJson())->toContain('"520"')
        ->and($options->carouselBreakpointsJson())->toContain('"760"');
});

it('preserves explicit grid responsive pattern as the mobile stack opt out', function (): void {
    $widget = new Widget([
        'meta' => [
            'responsive_layout_pattern' => ResponsiveLayoutPattern::Grid->value,
        ],
    ]);

    $options = ResponsiveAssetLayoutOptions::fromWidget($widget, 4);

    expect($options->pattern)->toBe(ResponsiveLayoutPattern::Grid)
        ->and($options->pattern->usesMobileCarousel())->toBeFalse()
        ->and($options->pattern->usesDesktopGrid())->toBeTrue();
});
