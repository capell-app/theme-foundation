<?php

declare(strict_types=1);

use Capell\Core\Actions\ResolveRenderableComponentAction;
use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\AssetComponentEnum;
use Capell\Core\Enums\RenderableTypeEnum;
use Capell\Core\Support\Renderables\RenderableRegistry;

it('resolves logical asset component keys before rendering foundation asset widgets', function (): void {
    $registry = new RenderableRegistry;
    $registry->register(new RenderableDefinitionData(
        key: AssetComponentEnum::Page->value,
        type: RenderableTypeEnum::Asset,
        blade: 'capell::page.asset',
    ));
    app()->instance(RenderableRegistry::class, $registry);

    expect(ResolveRenderableComponentAction::run(RenderableTypeEnum::Asset, AssetComponentEnum::Page->value))
        ->toBe('capell::page.asset');

    $themePath = dirname(__DIR__, 2);

    foreach (['index', 'widgets'] as $template) {
        $contents = file_get_contents($themePath . "/resources/views/components/widget/asset/{$template}.blade.php");

        expect($contents)
            ->toContain('use Capell\\Core\\Actions\\ResolveRenderableComponentAction;')
            ->toContain('use Capell\\Core\\Enums\\RenderableTypeEnum;')
            ->toContain('ResolveRenderableComponentAction::run(')
            ->not->toContain(':component="app(AssetsRegistryInterface::class)->getAsset($asset[\'asset_type\'])->component"');
    }
});
