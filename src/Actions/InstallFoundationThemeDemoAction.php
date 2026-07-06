<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\Demo\FoundationDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageInstaller;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static int run(ThemeDemoInstallData $data)
 */
final class InstallFoundationThemeDemoAction
{
    use AsAction;

    public function handle(ThemeDemoInstallData $data): int
    {
        return ThemeDemoPageInstaller::run(
            data: $data,
            themeKey: FoundationThemeServiceProvider::THEME_KEY,
            themeName: 'Foundation',
            contentProvider: new FoundationDemoContent,
        );
    }
}
