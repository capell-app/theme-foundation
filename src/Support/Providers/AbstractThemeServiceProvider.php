<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Illuminate\Support\ServiceProvider;
use Override;

abstract class AbstractThemeServiceProvider extends ServiceProvider
{
    public static string $packageName;

    private bool $themeBooted = false;

    abstract protected function bootTheme(ThemeRegistry $registry): void;

    #[Override]
    public function register(): void
    {
        // Installation refreshes callbacks on providers already loaded as metadata.
        $this->booted(function (): void {
            if (! $this->themeBooted && CapellCore::isPackageInstalled(static::$packageName)) {
                $this->boot($this->app->make(ThemeRegistry::class));
            }
        });
    }

    final public function boot(ThemeRegistry $registry): void
    {
        if ($this->themeBooted || ! CapellCore::isPackageInstalled(static::$packageName)) {
            return;
        }

        $this->bootTheme($registry);
        $this->themeBooted = true;
    }
}
