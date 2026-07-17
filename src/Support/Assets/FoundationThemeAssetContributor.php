<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Assets;

use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\Frontend\Contracts\FrontendResourceContributor;
use Capell\Frontend\Data\Assets\FrontendResourceContributionData;
use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\Assets\ViteResourceSourceData;
use Capell\Frontend\Data\FrontendResourceContextData;
use Symfony\Component\Filesystem\Path;

final class FoundationThemeAssetContributor implements FrontendResourceContributor
{
    public function resources(FrontendResourceContextData $context): array
    {
        $resources = [];
        $buildDirectory = $this->frontendBuildDirectory($context);

        if ($this->shouldLoadFrontendCss($context)) {
            $resources[] = FrontendResourceData::style(
                handle: 'capell-app/theme-foundation:frontend-style',
                package: FoundationThemeServiceProvider::$packageName,
                source: new ViteResourceSourceData($this->frontendCssPath(), $buildDirectory),
                criticalCssEligible: true,
            );
        }

        $themeCss = $this->themeCssResource($context, $buildDirectory);

        if ($themeCss instanceof FrontendResourceData) {
            $resources[] = $themeCss;
        }

        if ($this->shouldLoadRuntimeJavaScript($context)) {
            $resources[] = FrontendResourceData::moduleScript(
                handle: 'capell-app/theme-foundation:runtime',
                package: FoundationThemeServiceProvider::$packageName,
                source: new ViteResourceSourceData('resources/js/capell-frontend.js', 'vendor/capell-theme-foundation'),
            );
        }

        return array_map(
            static fn (FrontendResourceData $resource): FrontendResourceContributionData => new FrontendResourceContributionData($resource),
            $resources,
        );
    }

    private function frontendCssPath(): string
    {
        $path = config('capell-theme-foundation.tailwind.output_css', 'resources/css/capell/frontend.css');

        return is_string($path) && $path !== '' ? $path : 'resources/css/capell/frontend.css';
    }

    private function frontendBuildDirectory(FrontendResourceContextData $context): string
    {
        $buildDirectory = $context->theme?->getMeta('assets_path', 'build');

        return is_string($buildDirectory) && $buildDirectory !== '' ? $buildDirectory : 'build';
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

    private function themeCssResource(FrontendResourceContextData $context, string $buildDirectory): ?FrontendResourceData
    {
        $themeKey = $context->theme?->key;

        if (! is_string($themeKey)
            || $themeKey === ''
            || $themeKey === 'default'
            || ! config('capell-theme-foundation.tailwind.split_theme_css', true)) {
            return null;
        }

        $directory = config('capell-theme-foundation.tailwind.theme_css_output_directory', 'resources/css/capell/themes');
        $directory = is_string($directory) && $directory !== '' ? $directory : 'resources/css/capell/themes';
        $source = $this->projectRelativeThemeCssSource($directory, $themeKey);

        if ($source === null) {
            return null;
        }

        return FrontendResourceData::style(
            handle: 'capell-app/theme-foundation:theme-' . $themeKey,
            package: FoundationThemeServiceProvider::$packageName,
            source: new ViteResourceSourceData($source, $buildDirectory),
            criticalCssEligible: true,
        );
    }

    private function projectRelativeThemeCssSource(string $directory, string $themeKey): ?string
    {
        $projectPath = Path::canonicalize(base_path());
        $realProjectPath = realpath($projectPath);

        if ($realProjectPath === false) {
            return null;
        }

        $realProjectPath = Path::canonicalize($realProjectPath);
        $directoryPath = Path::isAbsolute($directory)
            ? Path::canonicalize($directory)
            : Path::makeAbsolute($directory, $projectPath);
        $realDirectoryPath = realpath($directoryPath);

        if ($realDirectoryPath === false) {
            return null;
        }

        $realDirectoryPath = Path::canonicalize($realDirectoryPath);

        if (! Path::isBasePath($projectPath, $directoryPath)
            || ! Path::isBasePath($realProjectPath, $realDirectoryPath)) {
            return null;
        }

        $sourcePath = Path::canonicalize($directoryPath . '/' . $themeKey . '.css');
        $realSourcePath = realpath($sourcePath);

        if (! Path::isBasePath($directoryPath, $sourcePath)
            || $realSourcePath === false
            || ! is_file($realSourcePath)) {
            return null;
        }

        $realSourcePath = Path::canonicalize($realSourcePath);

        if (! Path::isBasePath($realDirectoryPath, $realSourcePath)) {
            return null;
        }

        return str_replace('\\', '/', Path::makeRelative($sourcePath, $projectPath));
    }
}
