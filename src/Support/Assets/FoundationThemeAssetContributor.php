<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Assets;

use Capell\Frontend\Contracts\FrontendResourceContributor;
use Capell\Frontend\Data\Assets\FrontendResourceContributionData;
use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\Assets\ViteResourceSourceData;
use Capell\Frontend\Data\FrontendResourceContextData;

final class FoundationThemeAssetContributor implements FrontendResourceContributor
{
    public function resources(FrontendResourceContextData $context): array
    {
        $resources = [];

        if ($this->shouldLoadFrontendCss($context)) {
            $resources[] = new FrontendResourceContributionData(FrontendResourceData::style(
                handle: 'theme-foundation:css',
                package: 'capell-app/theme-foundation',
                source: new ViteResourceSourceData($this->frontendCssPath(), $this->frontendCssBuildPath($context)),
            ));
        }

        $themeCssResource = $this->themeCssResource($context);

        if ($themeCssResource instanceof FrontendResourceContributionData) {
            $resources[] = $themeCssResource;
        }

        if ($this->shouldLoadRuntimeJavaScript($context)) {
            $resources[] = new FrontendResourceContributionData(FrontendResourceData::moduleScript(
                handle: 'theme-foundation:runtime',
                package: 'capell-app/theme-foundation',
                source: new ViteResourceSourceData('resources/js/capell-frontend.js', 'vendor/capell-theme-foundation'),
            ));
        }

        return $resources;
    }

    private function frontendCssPath(): string
    {
        $path = config('capell-theme-foundation.tailwind.output_css', 'resources/css/capell/frontend.css');

        return is_string($path) && $path !== '' ? $path : 'resources/css/capell/frontend.css';
    }

    private function frontendCssBuildPath(FrontendResourceContextData $context): string
    {
        $buildPath = $context->theme?->getMeta('assets_path', 'build');

        return is_string($buildPath) && $buildPath !== '' ? $buildPath : 'build';
    }

    private function shouldLoadFrontendCss(FrontendResourceContextData $context): bool
    {
        $value = data_get($context->theme?->meta, 'frontend_runtime.uses_theme_foundation_css');

        return is_bool($value) ? $value : true;
    }

    private function shouldLoadRuntimeJavaScript(FrontendResourceContextData $context): bool
    {
        return $context->runtime->usesIslands
            || $context->runtime->usesLivewire
            || ($context->runtime->modules['theme-foundation-runtime'] ?? false);
    }

    /**
     * When capell-theme-foundation.tailwind.split_theme_css is enabled, emit
     * the active theme's own compiled bundle as an additional requirement —
     * every request context carries its own $context->theme, so multi-site
     * installs resolve the right per-theme file with no extra wiring.
     */
    private function themeCssResource(FrontendResourceContextData $context): ?FrontendResourceContributionData
    {
        $themeKey = $context->theme?->key;

        if (! is_string($themeKey) || $themeKey === '' || ! config('capell-theme-foundation.tailwind.split_theme_css', true)) {
            return null;
        }

        $directory = config('capell-theme-foundation.tailwind.theme_css_output_directory', 'resources/css/capell/themes');
        $directory = is_string($directory) && $directory !== '' ? $directory : 'resources/css/capell/themes';

        return new FrontendResourceContributionData(FrontendResourceData::style(
            handle: 'theme-css:' . $themeKey,
            package: 'capell-app/theme-foundation',
            source: new ViteResourceSourceData(
                rtrim($directory, '/') . '/' . $themeKey . '.css',
                $this->frontendCssBuildPath($context),
            ),
        ));
    }
}
