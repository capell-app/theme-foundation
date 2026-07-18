<?php

declare(strict_types=1);

use Capell\FoundationTheme\Tests\Fixtures\RegistersLayoutNativeThemeDefaultsTestProvider;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Blade;

/*
|--------------------------------------------------------------------------
| RegistersLayoutNativeThemeDefaults trait test
|--------------------------------------------------------------------------
|
| This is the shared theme-provider boilerplate extracted out of
| LiquidGlassThemeServiceProvider for reuse by future theme conversions.
| Liquid Glass's own tests (e.g. LiquidGlassVisualProofTest) exercise this
| trait indirectly through a real HTTP render of a page using both the
| chrome-override seam and the header/footer layout areas it registers.
| This test exercises the trait directly and in isolation, against a
| minimal throwaway ServiceProvider, so the trait's own contract is pinned
| down independently of any one theme's usage of it.
|
*/

it('registers both a plain view namespace and a matching anonymous-component namespace', function (): void {
    $provider = new RegistersLayoutNativeThemeDefaultsTestProvider($this->app);

    $viewsPath = __DIR__ . '/../Fixtures/views';

    $provider->registerViewNamespace('capell-theme-view-namespace-fixture', $viewsPath);

    /** @var ViewFactory $viewFactory */
    $viewFactory = resolve(ViewFactory::class);

    expect($viewFactory->exists('capell-theme-view-namespace-fixture::variant-base'))->toBeTrue();

    $componentNamespaces = Blade::getAnonymousComponentNamespaces();

    expect($componentNamespaces)->toHaveKey('capell-theme-view-namespace-fixture');
    expect($componentNamespaces['capell-theme-view-namespace-fixture'])->toBe('capell-theme-view-namespace-fixture::');
});

it('registers the shared header and footer layout-builder areas', function (): void {
    $provider = new RegistersLayoutNativeThemeDefaultsTestProvider($this->app);

    $registry = resolve(LayoutAreaRegistry::class);

    $provider->registerAreas();

    $areas = $registry->options();

    expect($areas)->toHaveKey('header');
    expect($areas)->toHaveKey('footer');
    expect($areas['header'])->toBe(__('capell-layout-builder::generic.header_area'));
    expect($areas['footer'])->toBe(__('capell-layout-builder::generic.footer_area'));
});
