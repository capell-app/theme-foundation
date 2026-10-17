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

it('keeps shared layout areas keyed and ordered across provider and registry resolutions', function (bool $shared, bool $resolved): void {
    $this->app->bind(LayoutAreaRegistry::class, static fn (): LayoutAreaRegistry => new LayoutAreaRegistry, $shared);

    if ($resolved) {
        $registry = resolve(LayoutAreaRegistry::class);
        $registry->register('header', 'Existing header');
        $registry->register('footer', 'Existing footer');
    }

    $provider = new RegistersLayoutNativeThemeDefaultsTestProvider($this->app);
    $provider->registerAreas();
    new RegistersLayoutNativeThemeDefaultsTestProvider($this->app)->registerAreas();

    $expected = [
        'main' => __('capell-layout-builder::generic.main_content_area'),
        'header' => __('capell-layout-builder::generic.header_area'),
        'footer' => __('capell-layout-builder::generic.footer_area'),
    ];

    $first = resolve(LayoutAreaRegistry::class);
    $second = resolve(LayoutAreaRegistry::class);

    expect($first->options())->toBe($expected)
        ->and($second->options())->toBe($expected)
        ->and($first === $second)->toBe($shared);

    $first->register('header', 'Theme header', 'fixture');
    $first->register('sidebar', 'Theme sidebar', 'fixture');

    $provider->registerAreas();

    expect($first->options())->toBe($expected)
        ->and($first->options('fixture'))->toBe([
            'main' => $expected['main'],
            'header' => 'Theme header',
            'footer' => $expected['footer'],
            'sidebar' => 'Theme sidebar',
        ]);
})->with([
    'unresolved singleton' => [true, false],
    'resolved singleton' => [true, true],
    'unresolved transient' => [false, false],
    'resolved transient' => [false, true],
]);
