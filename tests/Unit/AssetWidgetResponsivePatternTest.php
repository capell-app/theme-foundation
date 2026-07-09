<?php

declare(strict_types=1);

test('asset widget view contains the responsive grid to carousel pattern hooks', function (): void {
    $themePath = dirname(__DIR__, 2);

    $assetWidgetView = file_get_contents($themePath . '/resources/views/components/widget/asset/index.blade.php');

    expect($assetWidgetView)->toContain('ResponsiveAssetLayoutOptions::fromWidget')
        ->and($assetWidgetView)->toContain('usesMobileCarousel()')
        ->and($assetWidgetView)->toContain('data-carousel-breakpoints')
        ->and($assetWidgetView)->toContain('data-carousel-breakpoints-base="container"')
        ->and($assetWidgetView)->toContain('data-carousel-rows');
});

test('asset carousel images use valid responsive sizes hints', function (): void {
    $themePath = dirname(__DIR__, 2);

    $assetCarouselView = file_get_contents($themePath . '/resources/views/components/widget/asset/carousel.blade.php');

    expect($assetCarouselView)->not->toContain('20w')
        ->and($assetCarouselView)->toContain('sizes="(min-width: 1024px) 20rem, (min-width: 640px) 33vw, 80vw"');
});

test('foundation showcase grids use tablet-safe columns before desktop expansion', function (): void {
    $themePath = dirname(__DIR__, 2);

    $processStepsView = file_get_contents($themePath . '/resources/views/components/widget/modern/process-steps.blade.php');
    $statsView = file_get_contents($themePath . '/resources/views/components/widget/modern/stats-section.blade.php');
    $pricingView = file_get_contents($themePath . '/resources/views/components/widget/modern/pricing-table.blade.php');
    $themeCss = file_get_contents($themePath . '/resources/css/theme/theme.css');

    expect($processStepsView)->toContain('md:grid-cols-2 md:gap-6 lg:grid-cols-4')
        ->and($statsView)->toContain('md:grid-cols-2 lg:grid-cols-4')
        ->and($pricingView)->toContain('md:grid-cols-2 lg:grid-cols-3')
        ->and($themeCss)->toContain('@media (min-width: 901px) and (max-width: 1199px)')
        ->and($themeCss)->toContain('grid-template-columns: repeat(2, minmax(0, 1fr));');
});
