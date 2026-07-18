<?php

declare(strict_types=1);

use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Support\Assets\FoundationThemeAssetContributor;
use Capell\Frontend\Data\Assets\ViteResourceSourceData;
use Capell\Frontend\Data\FrontendResourceContextData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\FrontendResourceKind;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Illuminate\Filesystem\Filesystem;

it('contributes typed application CSS and conditional Foundation runtime resources', function (): void {
    $runtime = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);
    $context = new FrontendResourceContextData(null, null, null, null, null, $runtime);
    $resources = resolve(FoundationThemeAssetContributor::class)->resources($context);

    expect($resources)->toHaveCount(1)
        ->and($resources[0]->resource->kind)->toBe(FrontendResourceKind::Style)
        ->and($resources[0]->resource->source)->toBeInstanceOf(ViteResourceSourceData::class);

    $runtime->usesIslands = true;
    $resources = resolve(FoundationThemeAssetContributor::class)->resources($context);

    expect($resources)->toHaveCount(2)
        ->and($resources[1]->resource->handle)->toBe('capell-app/theme-foundation:runtime')
        ->and($resources[1]->resource->kind)->toBe(FrontendResourceKind::ModuleScript);
});

it('honours a theme opt out from the Foundation stylesheet', function (): void {
    $theme = Theme::factory()->create([
        'meta' => [
            'frontend_runtime' => [
                'uses_foundation_theme_css' => false,
            ],
        ],
    ]);
    $context = new FrontendResourceContextData(
        null,
        null,
        null,
        null,
        $theme,
        FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    );

    $resources = resolve(FoundationThemeAssetContributor::class)->resources($context);

    expect(collect($resources)->pluck('resource.handle')->all())
        ->not->toContain('capell-app/theme-foundation:frontend-style');
});

it('contributes the active split theme stylesheet', function (): void {
    $filesystem = resolve(Filesystem::class);
    $directory = storage_path('framework/testing/capell-theme-foundation-split-' . uniqid());
    $filesystem->ensureDirectoryExists($directory);
    $filesystem->put($directory . '/showreel.css', '.showreel {}');
    config()->set('capell-theme-foundation.tailwind.split_theme_css', true);
    config()->set('capell-theme-foundation.tailwind.theme_css_output_directory', $directory);

    try {
        $theme = Theme::factory()->create(['key' => 'showreel']);
        $context = new FrontendResourceContextData(
            null,
            null,
            null,
            null,
            $theme,
            FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
        );
        $resources = resolve(FoundationThemeAssetContributor::class)->resources($context);

        expect(collect($resources)->pluck('resource.handle')->all())
            ->toContain('capell-app/theme-foundation:theme-showreel');
    } finally {
        $filesystem->deleteDirectory($directory);
    }
});

it('omits an active split theme stylesheet that has not been generated', function (): void {
    config()->set('capell-theme-foundation.tailwind.split_theme_css', true);
    config()->set(
        'capell-theme-foundation.tailwind.theme_css_output_directory',
        storage_path('framework/testing/capell-theme-foundation-missing-' . uniqid()),
    );
    $theme = Theme::factory()->create(['key' => 'not-generated']);
    $context = new FrontendResourceContextData(
        null,
        null,
        null,
        null,
        $theme,
        FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    );
    $resources = resolve(FoundationThemeAssetContributor::class)->resources($context);

    expect(collect($resources)->pluck('resource.handle')->all())
        ->not->toContain('capell-app/theme-foundation:theme-not-generated');
});
