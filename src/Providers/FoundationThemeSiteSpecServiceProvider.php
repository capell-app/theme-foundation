<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Providers;

use Capell\Core\Support\Themes\ThemeInstallDefaultsRegistry;
use Capell\FoundationTheme\Actions\InstallFoundationThemeLayoutDefaultsAction;
use Illuminate\Support\ServiceProvider;

final class FoundationThemeSiteSpecServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $register = static function (ThemeInstallDefaultsRegistry $registry): void {
            $registry->register(
                FoundationThemeServiceProvider::THEME_KEY,
                static function (): void {
                    InstallFoundationThemeLayoutDefaultsAction::run();
                },
            );
        };

        $this->app->afterResolving(ThemeInstallDefaultsRegistry::class, $register);

        if ($this->app->resolved(ThemeInstallDefaultsRegistry::class)) {
            $register($this->app->make(ThemeInstallDefaultsRegistry::class));
        }
    }
}
