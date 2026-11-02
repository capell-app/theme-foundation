<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Enums\FoundationThemeAssetEnum;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\View\Components\Actions;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Support\Assets\FrontendAssetsService;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionDefinitionAdapter;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\FileViewFinder;

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

it('registers its own component namespace on boot when nothing is installed', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false, sourceRoot: dirname(__DIR__, 2));
        // `view:cache` compiles other packages' views that use these components whatever the
        // database says is installed. Start from view services that know nothing of this package,
        // on a database where nothing is installed, so shared rendering returns early.
        $app->instance('blade.compiler', new BladeCompiler($app->make('files'), $app->make('config')->string('view.compiled')));
        Blade::clearResolvedInstance('blade.compiler');
        $app->make(ViewFactory::class)->getFinder()->replaceNamespace('capell-theme-foundation', []);
        CapellCore::partialMock()->shouldReceive('isPackageInstalled')->andReturnFalse();

        new FoundationThemeServiceProvider($app)->packageBooted();

        $compiled = $app->make(BladeCompiler::class)->compileString('<x-capell-theme-foundation::widget.wrapper />');
        $finder = $app->make(ViewFactory::class)->getFinder();
        assert($finder instanceof FileViewFinder);

        expect($compiled)->toContain('capell-theme-foundation::components.widget.wrapper')
            ->and($finder->getHints()['capell-theme-foundation'] ?? [])->not->toBeEmpty()
            // Class-based components resolve through the same namespace.
            ->and($app->make(BladeCompiler::class)->compileString('<x-capell-theme-foundation::actions />'))
            ->toContain(Actions::class);
    });
});

it('registers its own component namespace once when installed', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true, sourceRoot: dirname(__DIR__, 2));
        expect(CapellCore::isPackageInstalled(FoundationThemeServiceProvider::$packageName))->toBeTrue();
        $finder = $app->make(ViewFactory::class)->getFinder();
        assert($finder instanceof FileViewFinder);
        $paths = array_map(realpath(...), $finder->getHints()['capell-theme-foundation'] ?? []);
        $anonymous = array_filter(
            $app->make(BladeCompiler::class)->getAnonymousComponentPaths(),
            static fn (array $path): bool => $path['prefix'] === 'capell-theme-foundation',
        );

        expect($paths)->toHaveCount(1)
            ->and($anonymous)->toHaveCount(1)
            ->and($app->make(BladeCompiler::class)->getClassComponentNamespaces()['capell-theme-foundation'] ?? null)
            ->toBe('Capell\\FoundationTheme\\View\\Components');
    });
});

it('lets a theme booted after it override a shared capell anonymous component before install', function (): void {
    $theme = sys_get_temp_dir() . '/capell-theme-override-' . bin2hex(random_bytes(4));
    mkdir($theme . '/button', 0o777, true);
    file_put_contents($theme . '/button/index.blade.php', 'theme override');

    try {
        InstalledRuntimeState::run(static function () use ($theme): void {
            $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false, static function (Application $app) use ($theme): void {
                // With neither Foundation nor Layout Builder installed, only the deferred fallback registers Foundation's components.
                CapellCore::forcePackageInstalled(LayoutBuilderServiceProvider::$packageName, false);
                // Registered after Foundation, so this theme boots after Foundation's packageBooted.
                $app->register(new class($app, $theme) extends ServiceProvider
                {
                    public function __construct(Application $app, private readonly string $theme)
                    {
                        parent::__construct($app);
                    }

                    public function boot(): void
                    {
                        Blade::anonymousComponentPath($this->theme, 'capell');
                    }
                });
            }, sourceRoot: dirname(__DIR__, 2));
            $finder = $app->make(ViewFactory::class)->getFinder();
            assert($finder instanceof FileViewFinder);

            expect(CapellCore::isPackageInstalled(FoundationThemeServiceProvider::$packageName))->toBeFalse()
                ->and(realpath($finder->find(hash('xxh128', 'capell') . '::button.index')))->toBe(realpath($theme . '/button/index.blade.php'))
                // Foundation's components remain the fallback for names the theme does not provide.
                ->and($app->make(BladeCompiler::class)->compileString('<x-capell::dropdown />'))->toContain('dropdown');
        });
    } finally {
        unlink($theme . '/button/index.blade.php');
        rmdir($theme . '/button');
        rmdir($theme);
    }
});

it('restores its anonymous component namespaces when the view finder is rebuilt', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false, sourceRoot: dirname(__DIR__, 2));
        $provider = $app->getProvider(FoundationThemeServiceProvider::class);
        assert($provider instanceof FoundationThemeServiceProvider);
        $finder = $app->make(ViewFactory::class)->getFinder();
        assert($finder instanceof FileViewFinder);
        $hash = hash('xxh128', 'capell-theme-foundation');
        $finder->replaceNamespace($hash, []);

        $provider->packageBooted();
        $provider->packageBooted();

        expect($finder->getHints()[$hash] ?? [])->toHaveCount(1)
            ->and(array_filter(
                $app->make(BladeCompiler::class)->getAnonymousComponentPaths(),
                static fn (array $path): bool => $path['prefix'] === 'capell-theme-foundation',
            ))->toHaveCount(1);
    });
});
