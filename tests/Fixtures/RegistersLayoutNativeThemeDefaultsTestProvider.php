<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\FoundationTheme\Support\Providers\RegistersLayoutNativeThemeDefaults;
use Illuminate\Support\ServiceProvider;

final class RegistersLayoutNativeThemeDefaultsTestProvider extends ServiceProvider
{
    use RegistersLayoutNativeThemeDefaults;

    public function registerViewNamespace(string $namespace, string $viewsPath): void
    {
        $this->registerThemeViewNamespace($namespace, $viewsPath);
    }

    public function registerAreas(): void
    {
        $this->registerStandardLayoutAreas();
    }
}
