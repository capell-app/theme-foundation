<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\FoundationTheme\Support\Providers\RegistersThemeFrontendScript;
use Illuminate\Support\ServiceProvider;

final class RegistersThemeFrontendScriptTestProvider extends ServiceProvider
{
    use RegistersThemeFrontendScript;

    public const string BUILD_DIRECTORY = 'vendor/capell/testing-theme';

    public const string ENTRY = 'resources/js/testing-theme.js';

    public const string PACKAGE_NAME = 'capell-app/testing-theme';

    public const string THEME_KEY = 'testing-theme';

    public function boot(): void
    {
        $this->registerThemeFrontendScript(
            themeKey: self::THEME_KEY,
            package: self::PACKAGE_NAME,
            entry: self::ENTRY,
            publicDirectory: self::BUILD_DIRECTORY,
            buildPath: dirname(__DIR__) . '/publishes/build',
        );
    }
}
