<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Providers;

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Closure;
use Illuminate\Support\Facades\Blade;

/**
 * Shared theme-provider boilerplate for layout-native theme packages
 * (themes that render through `x-capell::layout` + layout-builder instead of
 * a legacy section-rendering pipeline).
 *
 * Intended to be used by a theme's `ServiceProvider` (e.g.
 * `LiquidGlassThemeServiceProvider`), not by Core or by
 * `FoundationThemeServiceProvider` itself — Foundation is the original,
 * hand-rolled implementation this trait was extracted from, and stays on its
 * own hand-rolled registration so this extraction can be verified against a
 * real, already-working reference before other themes migrate to it.
 */
trait RegistersLayoutNativeThemeDefaults
{
    /**
     * Boot the invariant part of a definition-only, layout-native theme.
     *
     * The callback is intentionally theme-owned. It may register only new,
     * package-owned renderable keys or other scoped integrations; this seam
     * never replaces shared layout-widget keys and therefore cannot alter a
     * different active theme's rendering.
     */
    protected function bootLayoutNativeThemeDefaults(
        ThemeRegistry $registry,
        string $packageName,
        string $translationNamespace,
        string $translationsPath,
        string $viewNamespace,
        string $viewsPath,
        string $cssSource,
        string $cssCondition,
        ThemeDefinitionData $definition,
        ?Closure $registerThemeRenderables = null,
    ): bool {
        if (! CapellCore::isPackageInstalled($packageName)) {
            return false;
        }

        $this->loadTranslationsFrom($translationsPath, $translationNamespace);
        $this->registerThemeViewNamespace($viewNamespace, $viewsPath);

        CapellCore::registerVendorAsset(new VendorAssetData(
            type: VendorAssetEnum::TailwindImport,
            value: $cssSource,
            packageName: $packageName,
            condition: $cssCondition,
        ));
        CapellCore::registerVendorAsset(VendorAssetData::tailwindSource('resources/views/**/*.blade.php', $packageName));

        $this->registerStandardLayoutAreas();
        $registerThemeRenderables?->__invoke();

        $registry->register(definition: $definition);

        return true;
    }

    /**
     * Registers a theme's Blade views under both a plain view namespace and
     * an anonymous-component namespace of the same name.
     *
     * Both calls are required, and they are NOT redundant with each other:
     *
     * - `loadViewsFrom($viewsPath, $namespace)` registers the plain view
     *   namespace consulted by `view()` lookups, `@include`, and
     *   `RenderableRegistry`'s `view()->exists()` checks.
     * - `Blade::anonymousComponentNamespace($namespace . '::', $namespace)`
     *   registers a *separate* component namespace. This is required because
     *   `<x-dynamic-component>` — used by the `Theme::meta.header_file` /
     *   `footer_file` chrome-override seam to resolve a theme's header/footer
     *   view at render time — resolves component names through Laravel's
     *   component-namespace registry, which never consults the plain view
     *   namespace registered above. This was confirmed by tracing
     *   `ComponentTagCompiler::componentClass()` in the Laravel framework
     *   source: it resolves anonymous component namespaces directly and has
     *   no fallback path through `loadViewsFrom()`'s view-namespace registry.
     *
     * Skipping either call silently breaks one consumer while leaving the
     * other working, which is why both live together here instead of being
     * left for each theme to rediscover and re-explain.
     */
    protected function registerThemeViewNamespace(string $namespace, string $viewsPath): void
    {
        $this->loadViewsFrom($viewsPath, $namespace);

        Blade::anonymousComponentNamespace($namespace . '::', $namespace);
    }

    /**
     * Registers the shared, global `header` and `footer` layout-builder
     * areas that every layout-native theme needs, using the same
     * translation keys as `FoundationThemeServiceProvider`'s equivalent
     * registration.
     *
     * This is deliberately global (no `$themeKey` scoping): `header` and
     * `footer` are the same two areas every theme shares, not a
     * theme-specific region. Any additional, theme-specific named areas a
     * theme wants to register are out of scope for this method and should be
     * registered separately by that theme's own provider.
     *
     * Uses the `afterResolving`/`resolved` double-registration dance so this
     * still registers correctly regardless of whether `LayoutAreaRegistry`
     * has already been resolved by the time this runs (boot-order safety).
     */
    protected function registerStandardLayoutAreas(): void
    {
        $register = function (LayoutAreaRegistry $registry): void {
            $registry->register('header', __('capell-layout-builder::generic.header_area'));
            $registry->register('footer', __('capell-layout-builder::generic.footer_area'));
        };

        $this->app->afterResolving(LayoutAreaRegistry::class, $register);

        if ($this->app->resolved(LayoutAreaRegistry::class)) {
            $register($this->app->make(LayoutAreaRegistry::class));
        }
    }
}
