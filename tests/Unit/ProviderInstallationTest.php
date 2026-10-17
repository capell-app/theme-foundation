<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionDefinitionAdapter;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\View\Compilers\BladeCompiler;

it('registers installed theme widget Blade aliases after metadata has already booted', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false, sourceRoot: dirname(__DIR__, 2));
        $provider = $app->getProvider(FoundationThemeServiceProvider::class);
        expect($provider)->not->toBeNull()
            ->and(CapellCore::isPackageInstalled(FoundationThemeServiceProvider::$packageName))->toBeFalse();
        expect($app->make(BladeCompiler::class)->getClassComponentAliases())->not->toHaveKey('capell-app.auth-menu');
        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
        InstallPackageAction::run(CapellCore::getPackage(FoundationThemeServiceProvider::$packageName));
        expect($app->getProvider(FoundationThemeServiceProvider::class))->not->toBeNull()->toBe($provider);
        expect($app->make(BladeCompiler::class)->getClassComponentAliases()['capell-app.auth-menu'] ?? null)->toBe(WidgetExtensionDefinitionAdapter::GATED_COMPONENT);
        $aliases = $app->make(BladeCompiler::class)->getClassComponentAliases();
        RefreshInstalledPackageRuntimeAction::run(CapellCore::getPackage(FoundationThemeServiceProvider::$packageName));
        expect($app->getProvider(FoundationThemeServiceProvider::class))->not->toBeNull()->toBe($provider);
        expect($app->make(BladeCompiler::class)->getClassComponentAliases())->toBe($aliases);
    });
});
