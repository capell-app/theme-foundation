<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Actions\Install\RunArtisanCommandAction;
use Capell\Core\Contracts\PackageLifecycleAction;
use Capell\Core\Contracts\ProgressReporter;
use Capell\Core\Data\PackageData;
use Capell\Core\Support\Install\NullProgressReporter;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class SetupFoundationThemePackageAction implements PackageLifecycleAction
{
    use AsFake;
    use AsObject;

    public function handle(PackageData $package, array $arguments = [], ?ProgressReporter $reporter = null): void
    {
        $reporter ??= new NullProgressReporter;

        $reporter->report('Publishing Foundation theme frontend assets.');
        RunArtisanCommandAction::run('vendor:publish', ['--tag' => 'capell-theme-foundation-assets', '--force' => true], $reporter);

        $result = InstallFoundationThemeLayoutDefaultsAction::run((bool) ($arguments['--force'] ?? $arguments['force'] ?? false));

        $reporter->report(sprintf(
            'Foundation theme layout defaults installed. Created: %d, updated: %d, skipped: %d.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));
    }
}
