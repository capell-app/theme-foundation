<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Providers;

use Capell\Core\Actions\Install\RegisterCommandsInCurrentProcessAction;
use Capell\FoundationTheme\Console\Commands\DemoCommand;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

final class ConsoleServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([DemoCommand::class]);

        // Composer can add this provider to an already running installer.
        if ($this->app instanceof Application && $this->app->isBooted()) {
            RegisterCommandsInCurrentProcessAction::run([DemoCommand::class]);
        }
    }
}
