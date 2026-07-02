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
