<?php

declare(strict_types=1);

use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Actions\ResolveThemeFrontendScriptDataAction;
use Capell\FoundationTheme\Data\ThemeFrontendScriptData;
use Capell\FoundationTheme\Support\Assets\ThemeFrontendScriptContributor;
use Capell\FoundationTheme\Support\Assets\ThemeFrontendScriptRegistry;
use Capell\Frontend\Data\Assets\FrontendResourceContributionData;
use Capell\Frontend\Data\Assets\ViteResourceSourceData;
use Capell\Frontend\Data\FrontendResourceContextData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;

it('resolves a safe Vite theme frontend entry', function (): void {
    $script = ResolveThemeFrontendScriptDataAction::run(
        themeKey: 'example',
        packageName: 'capell-app/theme-example',
        entry: 'resources/js/example.js',
        publicDirectory: 'vendor/capell/themes/example',
    );

    expect($script)
        ->toBeInstanceOf(ThemeFrontendScriptData::class)
        ->and($script->handle)->toBe('capell-app/theme-example:frontend-runtime')
        ->and($script->entry)->toBe('resources/js/example.js')
        ->and($script->publicDirectory)->toBe('vendor/capell/themes/example');
});

it('contributes only the active themes registered module script', function (): void {
    $registry = new ThemeFrontendScriptRegistry;
    $registry->register(new ThemeFrontendScriptData(
        themeKey: 'concierge',
        handle: 'capell-app/theme-concierge:frontend-runtime',
        packageName: 'capell-app/theme-concierge',
        entry: 'resources/js/concierge-frontend.js',
        publicDirectory: 'vendor/capell/themes/concierge',
    ));

    $runtime = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);
    $context = new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: new Theme(['key' => 'concierge']),
        runtime: $runtime,
    );

    $resources = (new ThemeFrontendScriptContributor($registry))->resources($context);
    $resource = $resources[0] ?? null;

    if (! $resource instanceof FrontendResourceContributionData) {
        throw new RuntimeException('Expected one typed frontend resource contribution.');
    }

    $source = $resource->resource->source;

    if (! $source instanceof ViteResourceSourceData) {
        throw new RuntimeException('Expected the theme frontend script to use a Vite resource source.');
    }

    expect($resources)->toHaveCount(1)
        ->and($source->entry)->toBe('resources/js/concierge-frontend.js')
        ->and($source->buildDirectory)->toBe('vendor/capell/themes/concierge');

    $otherContext = new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: new Theme(['key' => 'business']),
        runtime: $runtime,
    );

    expect((new ThemeFrontendScriptContributor($registry))->resources($otherContext))->toBe([]);
});
