<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Assets;

use Capell\Frontend\Contracts\FrontendAssetContributor;
use Capell\Frontend\Data\FrontendAssetContextData;
use Capell\Frontend\Data\FrontendAssetRequirementData;

final class FoundationThemeAssetContributor implements FrontendAssetContributor
{
    public function requirements(FrontendAssetContextData $context): array
    {
        $requirements = [];

        if ($this->shouldLoadFrontendCss($context)) {
            $requirements[] = new FrontendAssetRequirementData(
                handle: 'theme-foundation:css',
                kind: FrontendAssetRequirementData::KIND_CSS,
                source: $this->frontendCssPath(),
                buildPath: $this->frontendCssBuildPath($context),
            );
        }

        if ($this->shouldLoadRuntimeJavaScript($context)) {
            $requirements[] = new FrontendAssetRequirementData(
                handle: 'theme-foundation:runtime',
                kind: FrontendAssetRequirementData::KIND_JS,
                source: 'resources/js/capell-frontend.js',
                buildPath: 'vendor/capell-theme-foundation',
                defer: true,
            );
        }

        return $requirements;
    }

    private function frontendCssPath(): string
    {
        $path = config('capell-theme-foundation.tailwind.output_css', 'resources/css/capell/frontend.css');

        return is_string($path) && $path !== '' ? $path : 'resources/css/capell/frontend.css';
    }

    private function frontendCssBuildPath(FrontendAssetContextData $context): string
    {
        $buildPath = $context->theme?->getMeta('assets_path', 'build');

        return is_string($buildPath) && $buildPath !== '' ? $buildPath : 'build';
    }

    private function shouldLoadFrontendCss(FrontendAssetContextData $context): bool
    {
        $value = data_get($context->theme?->meta, 'frontend_runtime.uses_theme_foundation_css');

        return is_bool($value) ? $value : true;
    }

    private function shouldLoadRuntimeJavaScript(FrontendAssetContextData $context): bool
    {
        return $context->runtime->usesIslands
            || $context->runtime->usesLivewire
            || ($context->runtime->modules['theme-foundation-runtime'] ?? false);
    }
}
