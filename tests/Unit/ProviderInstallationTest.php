<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Enums\FoundationThemeAssetEnum;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Support\Assets\FrontendAssetsService;
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

it('registers installed Foundation assets across registry resolution order and replacement', function (string $registryState): void {
    InstalledRuntimeState::run(static function () use ($registryState): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false, sourceRoot: dirname(__DIR__, 2));
        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
        unset($app[AssetsRegistryInterface::class]);
        if ($registryState !== 'unbound') {
            $app->singleton(AssetsRegistryInterface::class, FrontendAssetsService::class);
        }

        if ($registryState === 'resolved') {
            expect($app->make(AssetsRegistryInterface::class)->getAssets())->toBeEmpty();
        }

        InstallPackageAction::run(CapellCore::getPackage(FoundationThemeServiceProvider::$packageName));
        expect(CapellCore::isPackageInstalled(FoundationThemeServiceProvider::$packageName))->toBeTrue();
        if ($registryState === 'unbound') {
            // Prove activation really preceded this registry's binding.
            expect($app->bound(AssetsRegistryInterface::class))->toBeFalse();
            $app->singleton(AssetsRegistryInterface::class, FrontendAssetsService::class);
        }

        $registry = $app->make(AssetsRegistryInterface::class);
        foreach (FoundationThemeAssetEnum::cases() as $asset) {
            expect($registry->getAsset($asset->getAsset())->component)->toBe($asset->getComponent());
        }

        // Container resolution hooks must also initialise a replacement instance.
        unset($app[AssetsRegistryInterface::class]);
        $app->singleton(AssetsRegistryInterface::class, FrontendAssetsService::class);
        $replacement = $app->make(AssetsRegistryInterface::class);
        expect($replacement)->not->toBe($registry);
        foreach (FoundationThemeAssetEnum::cases() as $asset) {
            expect($replacement->getAsset($asset->getAsset())->component)->toBe($asset->getComponent());
        }
    });
})->with(['unbound', 'bound', 'resolved']);
