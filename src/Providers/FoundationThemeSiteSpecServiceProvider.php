<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Providers;

use Capell\Core\Support\Themes\ThemeInstallDefaultsRegistry;
use Capell\FoundationTheme\Actions\InstallFoundationThemeLayoutDefaultsAction;
use Illuminate\Support\ServiceProvider;
use Override;

final class FoundationThemeSiteSpecServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->callAfterResolving(ThemeInstallDefaultsRegistry::class, static function (ThemeInstallDefaultsRegistry $registry): void {
            if ($registry->has(FoundationThemeServiceProvider::THEME_KEY)) {
                return;
            }

            $registry->register(
                FoundationThemeServiceProvider::THEME_KEY,
                static function (): void {
                    InstallFoundationThemeLayoutDefaultsAction::run();
                },
            );
        });
    }
}
