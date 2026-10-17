<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Blade;
use Livewire\Blaze\Blaze;

require_once __DIR__ . '/../Support/WidgetViewFixture.php';

it('renders prepared assets through the public asset component aliases', function (string $alias): void {
    $widget = foundationWidgetViewFixture();
    foreach (foundationWidgetAssets($widget) as $index => $asset) {
        $asset->meta = ['title' => 'Prepared asset ' . ($index + 1)];
        $asset->setRelation('media', new Collection([MediaFactory::new()->make([
            'id' => 101 + $index, 'file_name' => 'prepared-image.jpg', 'mime_type' => 'image/jpeg',
            'custom_properties' => ['width' => 600, 'height' => 400],
        ])]));
    }
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        $html = Blade::render(
            '<x-' . $alias . ' :widget="$widget" :container="[]" container-key="main" :widget-index="0" :loop="$loop" />',
            ['widget' => $widget, 'loop' => (object) ['index' => 0, 'first' => true, 'last' => true]],
        );
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect($html)->toContain('Prepared asset 1', 'Prepared asset 2');
})->with([
    'capell::widget.asset',
    'capell::widget.asset.accordion',
    'capell::widget.asset.banners',
    'capell::widget.asset.carousel',
    'capell::widget.asset.features',
    'capell::widget.asset.media',
    'capell::widget.asset.testimonials',
    'capell::widget.asset.widgets',
]);

it('derives asset totals and honours an explicit presentation override', function (): void {
    $widget = foundationWidgetViewFixture();
    $derivedHtml = renderFoundationWidgetFixture('widgets', $widget, [
        'firstAssetRenderData' => null, 'lastAssetRenderData' => null,
    ]);
    $overrideHtml = renderFoundationWidgetFixture('widgets', $widget, [
        'total' => 3, 'firstAssetRenderData' => null, 'lastAssetRenderData' => null,
    ]);

    expect($derivedHtml)->toContain('--columns: 2')
        ->and($overrideHtml)->toContain('--columns: 3')
        ->and($overrideHtml)->toContain('Prepared asset 1', 'Prepared asset 2');
});
