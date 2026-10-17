<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Console\Commands\GenerateTailwindAssetsCommand;
use Capell\FoundationTheme\Http\Controllers\FoundationDynamicFormFragmentController;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\Frontend\Console\Commands\GenerateTailwindAssetsCommand as FrontendGenerateTailwindAssetsCommand;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

function bootFoundationBeforeLayoutBuilderInstallation(): Application
{
    return InstalledRuntimeTestCase::boot(
        InstalledRuntimeParity::manifest('layout-builder'),
        false,
        static function (Application $app): void {
            $app->register(FoundationThemeServiceProvider::class);
        },
        sourceRoot: dirname(__DIR__, 3) . '/layout-builder',
    );
}

/** @return list<Route> */
function lateFoundationDynamicFormRoutes(Application $app): array
{
    return array_values(array_filter(
        $app->make(Router::class)->getRoutes()->getRoutes(),
        static fn (Route $route): bool => $route->getActionName() === FoundationDynamicFormFragmentController::class,
    ));
}

it('isolates shared fragment registration during Layout Builder installation and restores nested route groups', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = bootFoundationBeforeLayoutBuilderInstallation();
        $router = $app->make(Router::class);
        $package = CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName);
        expect(lateFoundationDynamicFormRoutes($app))->toBe([]);
        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);

        $router->group(['as' => 'outer.', 'prefix' => 'outer', 'middleware' => 'outer-hook'], static function () use ($app, $router, $package): void {
            $outerStack = $router->getGroupStack();
            $router->group(['as' => 'inner.', 'prefix' => 'inner', 'middleware' => 'inner-hook'], static function () use ($app, $router, $package): void {
                $stack = $router->getGroupStack();
                InstallPackageAction::run($package);
                expect($router->getGroupStack())->toBe($stack);
                $routes = lateFoundationDynamicFormRoutes($app);
                expect($routes)->toHaveCount(1)
                    ->and($routes[0]->getName())->toBe('capell-theme-foundation.dynamic-form-fragments.show')
                    ->and($routes[0]->uri())->toBe('_capell/foundation-dynamic-form/{reference}')
                    ->and($routes[0]->middleware())->toBe(['web']);

                for ($refresh = 0; $refresh < 2; $refresh++) {
                    RefreshInstalledPackageRuntimeAction::run($package);
                    $app->make(Dispatcher::class)->dispatch(new PackageInstalled($package));
                    expect(lateFoundationDynamicFormRoutes($app))->toBe($routes)
                        ->and($router->getGroupStack())->toBe($stack);
                }

                $sibling = $router->get('sibling', static fn (): string => 'ok')->name('sibling');
                expect($sibling->getName())->toBe('outer.inner.sibling')
                    ->and($sibling->uri())->toBe('outer/inner/sibling')
                    ->and($sibling->middleware())->toBe(['outer-hook', 'inner-hook']);
            });
            expect($router->getGroupStack())->toBe($outerStack);
        });

        expect($router->getGroupStack())->toBe([]);
    });
});

it('replaces the warmed frontend command when Layout Builder activates shared rendering only once', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = bootFoundationBeforeLayoutBuilderInstallation();
        $kernel = $app->make(KernelContract::class);
        expect($kernel->all()['capell:frontend-tailwind-assets'])->toBeInstanceOf(FrontendGenerateTailwindAssetsCommand::class);
        $artisan = new ReflectionProperty(Kernel::class, 'artisan')->getValue($kernel);
        $package = CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName);
        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);

        InstallPackageAction::run($package);

        $command = $kernel->all()['capell:frontend-tailwind-assets'];
        expect($command)->toBeInstanceOf(GenerateTailwindAssetsCommand::class);
        $commands = $kernel->all();
        for ($refresh = 0; $refresh < 2; $refresh++) {
            RefreshInstalledPackageRuntimeAction::run($package);
            $app->make(Dispatcher::class)->dispatch(new PackageInstalled($package));
            expect($kernel->all())->toBe($commands)
                ->and($kernel->all()['capell:frontend-tailwind-assets'])->toBe($command)
                ->and(new ReflectionProperty(Kernel::class, 'artisan')->getValue($kernel))->toBe($artisan);
        }
    });
});

it('leaves a cold console application deferred until its normal startup', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = bootFoundationBeforeLayoutBuilderInstallation();
        $kernel = $app->make(KernelContract::class);
        $artisan = new ReflectionProperty(Kernel::class, 'artisan');
        expect($artisan->getValue($kernel))->toBeNull();
        $package = CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName);
        CapellCore::markPackageInstalled($package->name);

        $app->make(Dispatcher::class)->dispatch(new PackageInstalled($package));

        expect($artisan->getValue($kernel))->toBeNull()
            ->and($kernel->all()['capell:frontend-tailwind-assets'])->toBeInstanceOf(GenerateTailwindAssetsCommand::class);
    });
});

it('does not replace commands during shared rendering activation outside the console', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = bootFoundationBeforeLayoutBuilderInstallation();
        $kernel = $app->make(KernelContract::class);
        $command = $kernel->all()['capell:frontend-tailwind-assets'];
        expect($command)->toBeInstanceOf(FrontendGenerateTailwindAssetsCommand::class);
        new ReflectionProperty(Application::class, 'isRunningInConsole')->setValue($app, false);
        $package = CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName);
        CapellCore::markPackageInstalled($package->name);

        $app->make(Dispatcher::class)->dispatch(new PackageInstalled($package));

        expect($kernel->all()['capell:frontend-tailwind-assets'])->toBe($command);
    });
});
