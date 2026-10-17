<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\MediaFactory;
use Capell\FoundationTheme\Actions\BuildWidgetAssetRenderDataAction;
use Capell\LayoutBuilder\Enums\ResponsiveLayoutPattern;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection;

require_once __DIR__ . '/../Support/WidgetViewFixture.php';

test('asset widgets expose carousel options only for carousel layouts', function (): void {
    $carousel = foundationWidgetViewFixture(['responsive_layout_pattern' => ResponsiveLayoutPattern::DesktopGridMobileCarousel->value]);
    $carouselHtml = renderFoundationWidgetFixture('index', $carousel);
    $grid = foundationWidgetViewFixture(['responsive_layout_pattern' => ResponsiveLayoutPattern::Grid->value]);
    $gridHtml = renderFoundationWidgetFixture('index', $grid);

    expect($carouselHtml)->toContain('data-carousel-breakpoints=', 'data-carousel-breakpoints-base="container"', 'data-carousel-rows=')
        ->and($gridHtml)->not->toContain('data-carousel-breakpoints=')
        ->and($carouselHtml)->toContain('Prepared asset 1', 'Prepared asset 2');
});

test('asset carousel images use valid responsive sizes hints', function (): void {
    $widget = foundationWidgetViewFixture();
    $assets = foundationWidgetAssets($widget);
    foreach ($assets as $asset) {
        $media = MediaFactory::new()->make([
            'id' => 101, 'file_name' => 'prepared-image.jpg', 'mime_type' => 'image/jpeg',
            'custom_properties' => ['width' => 600, 'height' => 400],
        ]);
        $asset->setRelation('media', new Collection([$media]));
    }
    $items = $assets->map(fn (WidgetAsset $asset): array => [
        'widgetAsset' => $asset,
        'renderData' => BuildWidgetAssetRenderDataAction::run($asset),
    ]);
    $html = renderFoundationWidgetFixture('carousel', $widget, ['assetRenderDataItems' => $items]);

    expect($html)->toContain('prepared-image.jpg')
        ->toContain('sizes="(min-width: 1024px) 20rem, (min-width: 640px) 33vw, 80vw"')
        ->not->toContain('20w');
});
