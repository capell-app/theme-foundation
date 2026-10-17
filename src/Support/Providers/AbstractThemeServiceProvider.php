<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Providers;

use Capell\Core\Support\Packages\InstalledRuntimeLifecycle;
use Capell\Core\Support\Packages\RegistersInstalledRuntime;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Illuminate\Support\ServiceProvider;
use Override;

abstract class AbstractThemeServiceProvider extends ServiceProvider
{
    use RegistersInstalledRuntime;

    public static string $packageName;

    abstract protected function bootTheme(ThemeRegistry $registry): void;

    #[Override]
    public function register(): void
    {
        $this->registerInstalledRuntime(static::$packageName);
    }

    final public function boot(ThemeRegistry $registry): void
    {
        $this->app->make(InstalledRuntimeLifecycle::class)->refresh(static::$packageName);
    }

    protected function bootInstalledRuntime(): void
    {
        $this->bootTheme($this->app->make(ThemeRegistry::class));
    }
}
