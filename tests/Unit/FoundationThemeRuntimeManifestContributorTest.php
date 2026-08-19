<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\FoundationTheme\Support\AuthMenuWidget;
use Capell\FoundationTheme\Support\FoundationThemeRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Frontend\Support\State\FrontendState;

it('loads the Foundation runtime when the header contains the auth-menu placeholder', function (): void {
    $layout = new Layout([
        'containers' => [
            'header' => [
                'meta' => ['area' => 'header'],
                'widgets' => [AuthMenuWidget::block()],
            ],
        ],
    ]);
    $manifest = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);

    (new FoundationThemeRuntimeManifestContributor)->contribute(
        (new FrontendState)->withLayout($layout),
        $manifest,
    );

    expect($manifest->modules['theme-foundation-runtime'] ?? false)->toBeTrue();
});

it('does not load the Foundation runtime without the auth-menu placeholder', function (): void {
    $layout = new Layout(['containers' => ['header' => ['meta' => ['area' => 'header'], 'widgets' => []]]]);
    $manifest = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);

    (new FoundationThemeRuntimeManifestContributor)->contribute(
        (new FrontendState)->withLayout($layout),
        $manifest,
    );

    expect($manifest->modules)->not->toHaveKey('theme-foundation-runtime');
});
