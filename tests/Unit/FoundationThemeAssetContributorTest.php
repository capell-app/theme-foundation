<?php

declare(strict_types=1);

use Capell\Core\Models\Theme;
use Capell\Core\Support\Assets\VendorAssetConditionRegistry;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\Assets\FoundationThemeAssetContributor;
use Capell\Frontend\Data\FrontendAssetContextData;
use Capell\Frontend\Data\FrontendAssetRequirementData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Illuminate\Support\Facades\File;

it('declares only the foundation css asset for blade only pages', function (): void {
    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]->kind)->toBe(FrontendAssetRequirementData::KIND_CSS)
        ->and($requirements[0]->source)->toBe('resources/css/capell/frontend.css');
});

it('keeps the generated foundation css separate from theme meta assets', function (): void {
    config(['capell-theme-foundation.tailwind.split_theme_css' => false]);

    $theme = Theme::factory()->make([
        'meta' => [
            'assets' => ['resources/css/app.css'],
            'assets_path' => 'build',
        ],
    ]);

    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]->source)->toBe('resources/css/capell/frontend.css')
        ->and($requirements[0]->buildPath)->toBe('build');
});

it('allows a theme to opt out of the generated foundation frontend css', function (): void {
    $theme = Theme::factory()->make([
        'meta' => [
            'frontend_runtime' => [
                'uses_theme_foundation_css' => false,
            ],
        ],
    ]);

    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
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
    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::FullLivewire),
    ));

    expect(collect($requirements)->contains(
        fn (FrontendAssetRequirementData $requirement): bool => $requirement->handle === 'theme-foundation:runtime'
            && $requirement->kind === FrontendAssetRequirementData::KIND_JS,
    ))->toBeTrue();
});

it('does not load the foundation runtime for generic alpine chrome', function (): void {
    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
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

    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: $runtime,
    ));

    $context = new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: $runtime,
    );
    $registerVendorAssetConditions = new ReflectionMethod(FoundationThemeServiceProvider::class, 'registerVendorAssetConditions');
    $registerVendorAssetConditions->invoke(new FoundationThemeServiceProvider(app()));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-foundation:runtime')
        ->and(resolve(VendorAssetConditionRegistry::class)->passes('theme-foundation-runtime', $context))->toBeFalse();
});

it('does not load the foundation runtime for blade-only layout builder output alone', function (): void {
    $context = new FrontendAssetContextData(
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
    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements($context);
    $registerVendorAssetConditions = new ReflectionMethod(FoundationThemeServiceProvider::class, 'registerVendorAssetConditions');
    $registerVendorAssetConditions->invoke(new FoundationThemeServiceProvider(app()));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-foundation:runtime')
        ->and(resolve(VendorAssetConditionRegistry::class)->passes('theme-foundation-runtime', $context))->toBeFalse();
});

it('loads the foundation runtime for the explicit foundation runtime module', function (): void {
    $context = new FrontendAssetContextData(
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
    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements($context);
    $registerVendorAssetConditions = new ReflectionMethod(FoundationThemeServiceProvider::class, 'registerVendorAssetConditions');
    $registerVendorAssetConditions->invoke(new FoundationThemeServiceProvider(app()));

    expect(collect($requirements)->pluck('handle')->all())->toContain('theme-foundation:runtime')
        ->and(resolve(VendorAssetConditionRegistry::class)->passes('theme-foundation-runtime', $context))->toBeTrue();
});

it('loads the runtime from the foundation theme published build', function (): void {
    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::FullLivewire),
    ));

    expect(collect($requirements)->contains(
        fn (FrontendAssetRequirementData $requirement): bool => $requirement->handle === 'theme-foundation:runtime'
            && $requirement->source === 'resources/js/capell-frontend.js'
            && $requirement->buildPath === 'vendor/capell-theme-foundation',
    ))->toBeTrue();
});

it('omits the per-theme css requirement when the split flag is off', function (): void {
    config(['capell-theme-foundation.tailwind.split_theme_css' => false]);

    $theme = Theme::factory()->make(['key' => 'showreel']);

    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-css:showreel');
});

it('omits the per-theme css requirement when no generated source exists', function (): void {
    $outputDirectory = storage_path('framework/testing/foundation-contributor-missing');

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $outputDirectory,
    ]);
    File::deleteDirectory($outputDirectory);

    $theme = Theme::factory()->make(['key' => 'default']);

    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
    ));

    expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-css:default');
});

it('never emits a split requirement for the default theme when a stale source exists', function (): void {
    $outputDirectory = storage_path('framework/testing/foundation-contributor-stale-default');

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $outputDirectory,
    ]);
    File::ensureDirectoryExists($outputDirectory);
    File::put($outputDirectory . '/default.css', '/* stale */');

    try {
        $theme = Theme::factory()->make(['key' => 'default']);

        $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
            page: null,
            site: null,
            language: null,
            layout: null,
            theme: $theme,
            runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
        ));

        expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-css:default');
    } finally {
        File::deleteDirectory($outputDirectory);
    }
});

it('emits a project-relative source for an existing bundle in an absolute output directory', function (): void {
    $outputDirectory = storage_path('framework/testing/foundation-contributor-existing');

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $outputDirectory,
    ]);
    File::ensureDirectoryExists($outputDirectory);
    File::put($outputDirectory . '/showreel.css', '/* test */');

    try {
        $theme = Theme::factory()->make(['key' => 'showreel']);

        $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
            page: null,
            site: null,
            language: null,
            layout: null,
            theme: $theme,
            runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
        ));

        $requirement = collect($requirements)->first(
            fn (FrontendAssetRequirementData $requirement): bool => $requirement->handle === 'theme-css:showreel',
        );

        expect($requirement)->toBeInstanceOf(FrontendAssetRequirementData::class);

        /** @var FrontendAssetRequirementData $requirement */
        expect($requirement->kind)->toBe(FrontendAssetRequirementData::KIND_CSS)
            ->and($requirement->source)->toBe('storage/framework/testing/foundation-contributor-existing/showreel.css');
    } finally {
        File::deleteDirectory($outputDirectory);
    }
});

it('rejects a theme key that traverses outside the configured output directory', function (): void {
    $outputDirectory = storage_path('framework/testing/foundation-contributor-traversal/themes');
    $escapedSource = dirname($outputDirectory) . '/escaped.css';

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $outputDirectory,
    ]);
    File::ensureDirectoryExists($outputDirectory);
    File::put($escapedSource, '/* escaped */');

    try {
        $theme = Theme::factory()->make(['key' => '../escaped']);

        $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
            page: null,
            site: null,
            language: null,
            layout: null,
            theme: $theme,
            runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
        ));

        expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-css:../escaped');
    } finally {
        File::deleteDirectory(dirname($outputDirectory));
    }
});

it('rejects a split source symlink that escapes the configured output directory', function (): void {
    $testDirectory = storage_path('framework/testing/foundation-contributor-symlink');
    $outputDirectory = $testDirectory . '/themes';
    $outsideSource = $testDirectory . '/outside.css';

    config([
        'capell-theme-foundation.tailwind.split_theme_css' => true,
        'capell-theme-foundation.tailwind.theme_css_output_directory' => $outputDirectory,
    ]);
    File::ensureDirectoryExists($outputDirectory);
    File::put($outsideSource, '/* outside */');
    symlink($outsideSource, $outputDirectory . '/linked.css');

    try {
        $theme = Theme::factory()->make(['key' => 'linked']);

        $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
            page: null,
            site: null,
            language: null,
            layout: null,
            theme: $theme,
            runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly),
        ));

        expect(collect($requirements)->pluck('handle')->all())->not->toContain('theme-css:linked');
    } finally {
        File::deleteDirectory($testDirectory);
    }
});

it('never emits a per-theme css requirement without an active theme', function (): void {
    config(['capell-theme-foundation.tailwind.split_theme_css' => true]);

    $requirements = resolve(FoundationThemeAssetContributor::class)->requirements(new FrontendAssetContextData(
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
