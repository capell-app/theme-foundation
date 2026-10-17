<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Http\Controllers\FoundationDynamicFormFragmentController;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;

it('makes shared Foundation fragments available before an installed Layout Builder dependant activates', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(
            InstalledRuntimeParity::manifest('layout-builder'),
            true,
            static function (Application $app): void {
                $app->register(FoundationThemeServiceProvider::class);
            },
            sourceRoot: dirname(__DIR__, 3) . '/layout-builder',
        );

        expect(CapellCore::isPackageInstalled(FoundationThemeServiceProvider::$packageName))->toBeFalse();
        $expected = [
            'capell-theme-foundation.dynamic-form-fragments.show',
            '_capell/foundation-dynamic-form/{reference}',
            ['GET', 'HEAD'],
            ['web'],
            FoundationDynamicFormFragmentController::class,
        ];
        /** @var list<array<int, mixed>> $dependentRoutes */
        $dependentRoutes = $app->make('installed-runtime.dependent-routes');
        $dependentRoutes = array_map(static fn (array $route): array => array_slice($route, 0, 5), $dependentRoutes);
        expect($dependentRoutes)->toContain($expected);

        $router = $app->make(Router::class);
        $route = $router->getRoutes()->getByName($expected[0]);
        expect($route)->not->toBeNull()
            ->and($route?->uri())->toBe($expected[1])
            ->and($route?->middleware())->toBe($expected[3]);
    });
});

it('keeps shared fragment route order identical during fresh and late Layout Builder activation', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $configure = static function (Application $app): void {
            $app->register(FoundationThemeServiceProvider::class);
        };
        $names = [
            'capell-layout-builder.fragments.show',
            'capell-layout-builder.layout-widgets.localized.show',
            'capell-layout-builder.layout-widgets.show',
            'capell-theme-foundation.dynamic-form-fragments.show',
        ];
        $routes = static fn (Application $app): array => array_values(array_filter(
            InstalledRuntimeParity::routeInventory($app),
            static fn (array $route): bool => in_array($route[0], $names, true),
        ));
        $manifest = InstalledRuntimeParity::manifest('layout-builder');
        $root = dirname(__DIR__, 3) . '/layout-builder';
        $fresh = InstalledRuntimeTestCase::boot($manifest, true, $configure, sourceRoot: $root);
        $expected = $routes($fresh);
        expect(array_column($expected, 0))->toBe($names);

        $late = InstalledRuntimeTestCase::boot($manifest, false, $configure, sourceRoot: $root);
        expect($routes($late))->toBe([]);
        InstalledRuntimeTestCase::prepareLateInstallMigrations($late);
        InstallPackageAction::run(CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName));
        expect($routes($late))->toBe($expected);
    });
});

it('registers shared fragments when Foundation is loaded after Layout Builder has already activated', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(
            InstalledRuntimeParity::manifest('layout-builder'),
            true,
            sourceRoot: dirname(__DIR__, 3) . '/layout-builder',
        );
        $routes = static fn (): array => array_values(array_filter(
            InstalledRuntimeParity::routeInventory($app),
            static fn (array $route): bool => $route[4] === FoundationDynamicFormFragmentController::class,
        ));
        expect($routes())->toBe([]);

        $app->register(FoundationThemeServiceProvider::class);

        $registered = $routes();
        expect($registered)->toHaveCount(1)
            ->and($registered[0][0])->toBe('capell-theme-foundation.dynamic-form-fragments.show')
            ->and($registered[0][3])->toBe(['web']);
        $app->register(FoundationThemeServiceProvider::class, force: true);
        expect($routes())->toBe($registered);
    });
});
