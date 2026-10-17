<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Themes\ThemeChromeRegistry;
use Capell\Core\Support\Themes\ThemeInstallDefaultsRegistry;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Filament\Extenders\FoundationLayoutContainerSchemaExtender;
use Capell\FoundationTheme\Filament\Extenders\SectionVariantSchemaExtender;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Providers\FoundationThemeSiteSpecServiceProvider;
use Capell\FoundationTheme\Support\FoundationLayoutContainerThemePresentationProjector;
use Capell\FoundationTheme\Support\FoundationThemeRuntimeManifestContributor;
use Capell\FoundationTheme\Support\Fragments\FoundationSectionPublicLayoutWidgetPayloadContributor;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\LayoutBuilder\Contracts\Extenders\LayoutContainerSchemaExtender;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetSchemaExtender;
use Capell\LayoutBuilder\Contracts\LayoutContainerThemePresentationProjector;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\FileViewFinder;

it('registers site spec defaults once across fresh provider instances', function (bool $resolveFirst): void {
    InstalledRuntimeState::run(static function () use ($resolveFirst): void {
        $app = new Illuminate\Foundation\Application;
        $app->singleton(ThemeInstallDefaultsRegistry::class);

        if ($resolveFirst) {
            $app->make(ThemeInstallDefaultsRegistry::class);
        }

        $app->register(FoundationThemeSiteSpecServiceProvider::class);
        $app->register(FoundationThemeSiteSpecServiceProvider::class, force: true);

        expect($app->make(ThemeInstallDefaultsRegistry::class)->has('default'))->toBeTrue();
    });
})->with([true, false]);

it('boots shared rendering once per application and reapplies Blade aliases for a fresh provider', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true);
        $provider = $app->getProvider(FoundationThemeServiceProvider::class);
        throw_unless($provider instanceof FoundationThemeServiceProvider, LogicException::class);
        $hints = foundationViewHints($app);
        $rendering = foundationSharedRendering($app);
        expect($hints)->toHaveCount(1)
            ->and($rendering['ctaComposers'])->toBe(1)
            ->and($rendering['runtimeContributors'])->toContain(FoundationThemeRuntimeManifestContributor::class)
            ->and($rendering['payloadContributors'])->toContain(FoundationSectionPublicLayoutWidgetPayloadContributor::class);
        $compiler = $app->make(BladeCompiler::class);
        $body = $compiler->getClassComponentAliases()['capell::app.body'];

        $compiler->component('frontend-fallback::components.app.body', 'capell::app.body');
        $provider->packageBooted();
        expect(foundationViewHints($app))->toBe($hints)
            ->and($compiler->getClassComponentAliases()['capell::app.body'])->toBe('frontend-fallback::components.app.body');

        $app->register(FoundationThemeServiceProvider::class, force: true);

        expect($compiler->getClassComponentAliases()['capell::app.body'])->toBe($body)
            ->and(foundationDynamicFormRouteCount($app))->toBe(1)
            ->and(foundationSharedRendering($app))->toBe($rendering);

        $app->register(FoundationThemeServiceProvider::class, force: true);

        expect(foundationSharedRendering($app))->toBe($rendering);
    });
});

it('completes shared rendering during late installation with a pre-registered component namespace', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false, static function (Application $app): void {
            CapellCore::forcePackageInstalled(LayoutBuilderServiceProvider::$packageName, false);
            $app->make(BladeCompiler::class)->componentNamespace('Capell\\FoundationTheme\\View\\Components', 'capell-theme-foundation');
        }, sourceRoot: dirname(__DIR__, 2));
        $compiler = $app->make(BladeCompiler::class);
        expect(foundationViewHints($app))->toBe([])
            ->and($compiler->getCustomDirectives())->not->toHaveKey('capellBuffer');

        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
        InstallPackageAction::run(CapellCore::getPackage(FoundationThemeServiceProvider::$packageName));

        $rendering = foundationSharedRendering($app);
        expect(foundationViewHints($app))->toHaveCount(1)
            ->and($app->make(Factory::class)->exists('capell-theme-foundation::components.actions.index'))->toBeTrue()
            ->and($compiler->getCustomDirectives())->toHaveKey('capellBuffer')
            ->and($compiler->getClassComponentAliases())->toHaveKey('capell::app.body')
            ->and($rendering['ctaComposers'])->toBe(1)
            ->and($rendering['runtimeContributors'])->toContain(FoundationThemeRuntimeManifestContributor::class)
            ->and($rendering['payloadContributors'])->toContain(FoundationSectionPublicLayoutWidgetPayloadContributor::class);

        $app->make(Dispatcher::class)->dispatch(new PackageInstalled(CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName)));
        $app->register(FoundationThemeServiceProvider::class, force: true);

        expect(foundationSharedRendering($app))->toBe($rendering)
            ->and(foundationDynamicFormRouteCount($app))->toBe(1);
    });
});

/** @return array<string, mixed> */
function foundationSharedRendering(Application $app): array
{
    $finder = $app->make(Factory::class)->getFinder();
    throw_unless($finder instanceof FileViewFinder, LogicException::class, 'Expected the file view finder.');
    $listeners = $app->make(Dispatcher::class)->getRawListeners();
    $ctaComposers = $listeners['composing: capell-theme-foundation::components.widget.modern.cta-section'] ?? [];
    throw_unless(is_array($ctaComposers), LogicException::class, 'Expected registered view composer occurrences.');
    $contributors = static fn (string $tag): array => array_map(
        static fn (object $contributor): string => $contributor::class,
        iterator_to_array($app->tagged($tag), false),
    );

    return [
        'hints' => $finder->getHints(),
        'anonymousPaths' => $app->make(BladeCompiler::class)->getAnonymousComponentPaths(),
        'ctaComposers' => count($ctaComposers),
        'runtimeContributors' => $contributors(FrontendRuntimeManifestContributor::TAG),
        'payloadContributors' => $contributors(PublicLayoutWidgetPayloadContributor::TAG),
    ];
}

/** @return list<Route> */
function foundationDynamicFormRoutes(Application $app): array
{
    return array_values(array_filter(
        $app->make(Router::class)->getRoutes()->getRoutes(),
        static fn (Route $route): bool => $route->getName() === 'capell-theme-foundation.dynamic-form-fragments.show',
    ));
}

function foundationDynamicFormRouteCount(Application $app): int
{
    return count(foundationDynamicFormRoutes($app));
}

/** @return list<string> */
function foundationViewHints(Application $app): array
{
    $finder = $app->make(Factory::class)->getFinder();
    throw_unless($finder instanceof FileViewFinder, LogicException::class, 'Expected the file view finder.');

    return array_values($finder->getHints()['capell-theme-foundation'] ?? []);
}

it('registers the dynamic form fragment route once when a fresh provider instance boots again', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true);
        $routes = foundationDynamicFormRoutes($app);
        expect($routes)->toHaveCount(1);

        $app->register(FoundationThemeServiceProvider::class, force: true);

        expect(foundationDynamicFormRoutes($app))->toBe($routes);
    });
});

it('contributes to registries resolved after installed runtime activation', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true);

        foreach ([ThemeChromeRegistry::class, ThemeRegistry::class, LayoutAreaRegistry::class] as $registry) {
            $app->forgetInstance($registry);
            $app->singleton($registry);
        }

        expect($app->make(ThemeChromeRegistry::class)->headerOptions())->toHaveKey('capell::header.index')
            ->and($app->make(ThemeChromeRegistry::class)->footerOptions())->toHaveKey('capell::footer')
            ->and($app->make(ThemeRegistry::class)->definition('default')->package)->toBe(FoundationThemeServiceProvider::$packageName)
            ->and($app->make(LayoutAreaRegistry::class)->options())->toHaveKeys(['main', 'header', 'footer']);
    });
});

it('does not repeat shared rendering when Layout Builder reports installation again', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true);
        $hints = foundationViewHints($app);
        expect($hints)->toHaveCount(1);

        $app->make(Dispatcher::class)->dispatch(new PackageInstalled(CapellCore::getPackage(LayoutBuilderServiceProvider::$packageName)));

        expect(foundationViewHints($app))->toBe($hints)
            ->and(foundationDynamicFormRouteCount($app))->toBe(1);
    });
});

it('tags its schema extenders during late installation without constructing other tagged services', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false, sourceRoot: dirname(__DIR__, 2));
        $constructed = 0;
        $app->bind('foundation-test.tagged-probe', static function () use (&$constructed): stdClass {
            $constructed++;

            return new stdClass;
        });
        $app->tag('foundation-test.tagged-probe', [
            LayoutContainerSchemaExtender::TAG,
            WidgetSchemaExtender::TAG,
            LayoutContainerThemePresentationProjector::TAG,
        ]);

        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
        InstallPackageAction::run(CapellCore::getPackage(FoundationThemeServiceProvider::$packageName));

        expect(CapellCore::isPackageInstalled(FoundationThemeServiceProvider::$packageName))->toBeTrue()
            ->and($constructed)->toBe(0);

        $instances = static fn (string $tag, string $class): int => count(array_filter(
            iterator_to_array($app->tagged($tag), false),
            static fn (object $service): bool => $service instanceof $class,
        ));
        expect($instances(LayoutContainerSchemaExtender::TAG, FoundationLayoutContainerSchemaExtender::class))->toBe(1)
            ->and($instances(WidgetSchemaExtender::TAG, SectionVariantSchemaExtender::class))->toBe(1)
            ->and($instances(LayoutContainerThemePresentationProjector::TAG, FoundationLayoutContainerThemePresentationProjector::class))->toBe(1);
    });
});
