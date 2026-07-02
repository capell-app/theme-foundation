<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Install\ConsoleProgressReporter;
use Capell\FoundationTheme\Actions\SetupFoundationThemePackageAction;
use Illuminate\Console\Command;

final class SetupCommand extends Command
{
    protected $signature = 'capell:theme-foundation-setup {--force : Rebuild Foundation-managed layout defaults}';

    protected $description = 'Install Foundation theme layout defaults.';

    public function handle(): int
    {
        SetupFoundationThemePackageAction::run(
            CapellCore::getPackage('capell-app/theme-foundation'),
            ['--force' => (bool) $this->option('force')],
            new ConsoleProgressReporter($this),
        );

        return self::SUCCESS;
    }
}
