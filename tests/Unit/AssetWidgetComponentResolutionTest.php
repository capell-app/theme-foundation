<?php

declare(strict_types=1);

require_once __DIR__ . '/../Support/WidgetViewFixture.php';

it('renders logical asset component keys through the registered renderer', function (string $view): void {
    $widget = foundationWidgetViewFixture();
    $html = renderFoundationWidgetFixture($view, $widget, [
        'firstAssetRenderData' => null, 'lastAssetRenderData' => null,
    ]);

    expect($html)->toContain('Prepared asset 1', 'Prepared asset 2')
        ->not->toContain('fixture.asset', 'asset_type');
})->with(['index', 'widgets']);
