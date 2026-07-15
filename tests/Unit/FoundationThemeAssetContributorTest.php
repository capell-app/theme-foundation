<?php

declare(strict_types=1);

use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Support\Assets\FoundationThemeAssetContributor;
use Capell\Frontend\Data\Assets\FrontendResourceContributionData;
use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\FrontendResourceContextData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\FrontendResourceKind;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Illuminate\Support\Facades\File;

/**
 * @return array<int, FrontendResourceData>
 */
function foundationThemeResources(FrontendResourceContextData $context): array
{
    return array_map(
        static fn (FrontendResourceContributionData $contribution): FrontendResourceData => $contribution->resource,
        resolve(FoundationThemeAssetContributor::class)->resources($context),
    );
}

it('declares only the foundation css asset for blade only pages', function (): void {
    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]->kind)->toBe(FrontendResourceKind::Style)
        ->and($requirements[0]->source->entry)->toBe('resources/css/capell/frontend.css');
});

it('keeps the generated foundation css separate from theme meta assets', function (): void {
    config(['capell-theme-foundation.tailwind.split_theme_css' => false]);

    $theme = Theme::factory()->make([
        'meta' => [
            'assets' => ['resources/css/app.css'],
            'assets_path' => 'build',
        ],
    ]);

    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]->source->entry)->toBe('resources/css/capell/frontend.css')
        ->and($requirements[0]->source->buildDirectory)->toBe('build');
});

it('allows a theme to opt out of the generated foundation frontend css', function (): void {
    $theme = Theme::factory()->make([
        'meta' => [
            'frontend_runtime' => [
                'uses_theme_foundation_css' => false,
            ],
        ],
    ]);

    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-foundation:css');
});

it('declares runtime javascript only when the frontend runtime needs javascript', function (): void {
    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::FullLivewire),
    ));

    expect(collect($requirements)->contains(
        fn (FrontendResourceData $requirement): bool => $requirement->handle === 'theme-foundation:runtime'
            && $requirement->kind === FrontendResourceKind::ModuleScript,
    ))->toBeTrue();
});

it('does not load the foundation runtime for generic alpine chrome', function (): void {
    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: new FrontendRuntimeManifestData(
            renderingStrategy: RenderingStrategyEnum::BladeOnly,
            usesLivewire: false,
            usesAlpine: true,
            usesBeacon: false,
            usesWireNavigate: false,
            usesIslands: false,
            modules: ['frontend-chrome' => true],
        ),
    ));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-foundation:runtime');
});

it('does not load the foundation runtime for the frontend authoring beacon alone', function (): void {
    $runtime = new FrontendRuntimeManifestData(
        renderingStrategy: RenderingStrategyEnum::BladeOnly,
        usesLivewire: false,
        usesAlpine: true,
        usesBeacon: true,
        usesWireNavigate: false,
        usesIslands: false,
        modules: ['frontend-chrome' => true, 'frontend-authoring' => true],
    );

    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: $runtime,
    ));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-foundation:runtime');
});

it('does not load the foundation runtime for blade-only layout builder output alone', function (): void {
    $context = new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: new FrontendRuntimeManifestData(
            renderingStrategy: RenderingStrategyEnum::BladeOnly,
            usesLivewire: false,
            usesAlpine: true,
            usesBeacon: false,
            usesWireNavigate: false,
            usesIslands: false,
            modules: ['layout-builder' => true],
        ),
    );
    $requirements = foundationThemeResources($context);
    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-foundation:runtime');
});

it('loads the foundation runtime for the explicit foundation runtime module', function (): void {
    $context = new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: new FrontendRuntimeManifestData(
            renderingStrategy: RenderingStrategyEnum::BladeOnly,
            usesLivewire: false,
            usesAlpine: true,
            usesBeacon: false,
            usesWireNavigate: false,
            usesIslands: false,
            modules: ['theme-foundation-runtime' => true],
        ),
    );
    $requirements = foundationThemeResources($context);
    expect(collect($requirements)->pluck('handle')->all())->toContain('theme-foundation:runtime');
});

it('loads the runtime from the foundation theme published build', function (): void {
    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::FullLivewire),
    ));

    expect(collect($requirements)->contains(
        fn (FrontendResourceData $requirement): bool => $requirement->handle === 'theme-foundation:runtime'
            && $requirement->source->entry === 'resources/js/capell-frontend.js'
            && $requirement->source->buildDirectory === 'vendor/capell-theme-foundation',
    ))->toBeTrue();
});

it('omits the per-theme css requirement when the split flag is off', function (): void {
    config(['capell-theme-foundation.tailwind.split_theme_css' => false]);

    $theme = Theme::factory()->make(['key' => 'showreel']);

    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-css:showreel');
});

it('emits the active theme own compiled bundle when the split flag is on', function (): void {
    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => 'resources/css/capell/themes',
    ]);
    File::ensureDirectoryExists(base_path('resources/css/capell/themes'));
    File::put(base_path('resources/css/capell/themes/showreel.css'), '/* test */');

    $theme = Theme::factory()->make(['key' => 'showreel']);

    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    $requirement = collect($requirements)->first(
        fn (FrontendResourceData $requirement): bool => $requirement->handle === 'theme-css:showreel',
    );

    expect($requirement)->toBeInstanceOf(FrontendResourceData::class);

    /** @var FrontendResourceData $requirement */
    expect($requirement->kind)->toBe(FrontendResourceKind::Style)
        ->and($requirement->source->entry)->toBe('resources/css/capell/themes/showreel.css');

    File::delete(base_path('resources/css/capell/themes/showreel.css'));
});

it('omits an active theme bundle until its split css source has been generated', function (): void {
    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => 'resources/css/capell/themes',
    ]);
    File::delete(base_path('resources/css/capell/themes/not-generated.css'));

    $theme = Theme::factory()->make(['key' => 'not-generated']);
    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-css:not-generated');
});

it('never emits a per-theme css requirement without an active theme', function (): void {
    config(['capell-theme-foundation.tailwind.split_theme_css' => true]);

    $requirements = foundationThemeResources(new FrontendResourceContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    $themeCssHandles = collect($requirements)
        ->pluck('handle')
        ->filter(fn (mixed $handle): bool => is_string($handle) && str_starts_with($handle, 'theme-css:'));

    expect($themeCssHandles)->toBeEmpty();
});
