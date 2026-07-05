<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\ThemeStudio\LiquidGlass\LiquidGlassThemeServiceProvider;

use function Pest\Laravel\get;

require_once __DIR__ . '/../../../../tests/Packages/Support/ThemeLayoutNativeSupport.php';

/*
|--------------------------------------------------------------------------
| Layout-native main content renders real widgets (regression)
|--------------------------------------------------------------------------
|
| Regression coverage for a bug where a layout-native theme's seeded
| homepage (real Layout::containers, real layout-builder widgets, no
| ThemeRenderer) rendered a 200 OK response over BladeOnly HTTP with an
| entirely empty main content area: no `id="main"` landmark anywhere in the
| response, and none of the container's widgets rendered — not even the
| shared `page-content` widget's own page title/content.
|
| Root cause: `Capell\FoundationTheme\View\Components\Layout\Main`
| (aliased as the `capell::layout.main` Blade component) renders
| layout-builder's dynamic, render-hook-driven main content. Livewire's
| Blaze package statically compiles the SIBLING component tags in
| `capell::layout.index` (header, content, logo) into inlined function
| calls that share Blaze's own output-buffering bookkeeping. When
| `<x-capell::layout.main>` is invoked immediately after those
| Blaze-compiled calls in the same buffer scope, Laravel's native `$__env`
| component stack desyncs and `capell::layout.main` silently renders empty
| output instead of the page's real widgets — no exception, 200 OK.
|
| The fix disables Blaze around the `<x-capell::layout.main>` invocation in
| `capell::components.layout.index`, mirroring the same defence
| `capell-layout-builder::components.layout.widget` and
| `Capell\LayoutBuilder\Support\RenderHooks\RegisterMainContentLayoutHook`
| already use around their own dynamic, hook-driven renders.
|
| This lives in theme-foundation (not tests/Packages) because the fix
| itself is in theme-foundation's own `capell::components.layout.index`
| view, and `tests/Packages/PackagesTestCase` is the base test case that
| already registers Blaze optimization for theme-foundation's `components`
| views — the exact condition that reproduces the bug — so any test in
| `tests/Packages` (including this one) exercises the fix automatically.
|
*/

it('renders real layout-builder widget content in the main landmark for a layout-native theme', function (): void {
    $registry = resolve(ThemeRegistry::class);
    $registry->reset();

    [$pageUrl, $pageTitle] = layoutNativeThemeCreatePage(LiquidGlassThemeServiceProvider::THEME_KEY, 'Main Content Regression');

    expect($registry->hasRenderer(LiquidGlassThemeServiceProvider::THEME_KEY))->toBeFalse();

    $response = get($pageUrl->full_url);

    $response->assertOk();

    $html = $response->getContent();

    expect($html)->toBeString();

    expect($html)
        ->toContain('id="main"')
        ->toContain(e($pageTitle))
        ->toContain('Teams that ship on the glass')
        ->toContain('One glass system, three token-driven presets');

    $registry->reset();
    CapellCore::clearPackages();
});
