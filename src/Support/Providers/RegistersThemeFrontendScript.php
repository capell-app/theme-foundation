<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Providers;

use Capell\FoundationTheme\Actions\ResolveThemeFrontendScriptDataAction;
use Capell\FoundationTheme\Support\Assets\ThemeFrontendScriptRegistry;

trait RegistersThemeFrontendScript
{
    protected function registerThemeFrontendScript(
        string $themeKey,
        string $package,
        string $entry,
        string $publicDirectory,
        string $buildPath,
    ): void {
        $this->publishes(
            [$buildPath => public_path($publicDirectory)],
            'capell-theme-assets',
        );

        resolve(ThemeFrontendScriptRegistry::class)->register(
            ResolveThemeFrontendScriptDataAction::run(
                themeKey: $themeKey,
                packageName: $package,
                entry: $entry,
                publicDirectory: $publicDirectory,
            ),
        );
    }
}
