<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Rendering\BladeThemeRenderer;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Actions\ValidateThemeCatalogueEntryAction;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;

require_once __DIR__ . '/../Support/ThemeCatalogueScreenshotSurfaceGap.php';

it('declares foundation as the default theme package', function (): void {
    $manifest = themePackageManifest('theme-foundation');
    $composer = themePackageComposer('theme-foundation');

    expect($manifest['name'])->toBe('capell-app/theme-foundation')
        ->and($composer['name'])->toBe('capell-app/theme-foundation')
        ->and($manifest['manifest-version'])->toBe(3)
        ->and($manifest['kind'])->toBe('theme')
        ->and($manifest['themeKey'])->toBe('default')
        ->and($manifest['extends'])->toBeNull();
});

it('registers only the shipped foundation theme service provider', function (): void {
    $manifest = themePackageManifest('theme-foundation');
    $composer = themePackageComposer('theme-foundation');

    expect($manifest['providers']['runtime'])->toBe([
        FoundationThemeServiceProvider::class,
    ])
        ->and($manifest['providers']['admin'])->toBe([])
        ->and($manifest['providers']['frontend'])->toBe([])
        ->and($composer['extra']['laravel']['providers'])->toBe([
            FoundationThemeServiceProvider::class,
        ]);
});

it('does not declare deferred route contributions because the theme ships no routes', function (): void {
    $manifest = themePackageManifest('theme-foundation');

    expect(data_get($manifest, 'contributes'))->toBe([])
        ->and(data_get($manifest, 'security.publicSurface.routeNames'))->toBe([])
        ->and(data_get($manifest, 'contributionTraceability.deferredContributions'))->toBe([])
        ->and(glob(dirname(__DIR__, 2) . '/routes/*.php') ?: [])->toBe([]);
});

it('defines the Foundation Theme Studio parent contract', function (): void {
    $definition = FoundationThemeServiceProvider::definition();

    expect($definition->key)->toBe('default')
        ->and($definition->package)->toBe('capell-app/theme-foundation')
        ->and($definition->extends)->toBeNull()
        ->and($definition->includedSections)->toContain('navigation')
        ->and($definition->includedSections)->toContain('hero')
        ->and($definition->includedSections)->toContain('features')
        ->and($definition->includedSections)->toContain('proof')
        ->and($definition->includedSections)->toContain('content-listing')
        ->and($definition->includedSections)->toContain('cta')
        ->and($definition->includedSections)->toContain('footer')
        ->and($definition->presets)->toHaveCount(1)
        ->and($definition->runtime->value)->toBe('blade')
        ->and($definition->assets)->toHaveKey('css');
});

it('registers a Theme Studio definition that matches the manifest', function (): void {
    $manifest = themePackageManifest('theme-foundation');

    $registry = new ThemeRegistry;
    $registry->register(
        FoundationThemeServiceProvider::definition(),
        new BladeThemeRenderer(
            themeKey: FoundationThemeServiceProvider::THEME_KEY,
            layoutView: 'capell-theme-foundation::theme.page',
            sectionRenderers: [],
        ),
        [],
    );

    expect($registry->has($manifest['themeKey']))->toBeTrue();

    $registered = $registry->definition($manifest['themeKey']);

    expect($registered->key)->toBe($manifest['themeKey'])
        ->and($registered->key)->toBe(FoundationThemeServiceProvider::THEME_KEY)
        ->and($registered->package)->toBe($manifest['name'])
        ->and($registered->extends)->toBe($manifest['extends']);
});

it('declares committed marketplace screenshots', function (): void {
    $manifest = themePackageManifest('theme-foundation');
    $screenshots = data_get($manifest, 'marketplace.screenshots');

    throw_unless(is_array($screenshots), RuntimeException::class, 'Foundation Theme marketplace screenshots must be an array.');

    $paths = collect($screenshots)
        ->map(function (mixed $screenshot): string {
            throw_unless(is_array($screenshot), RuntimeException::class, 'Foundation Theme marketplace screenshot entries must be arrays.');

            $path = $screenshot['path'] ?? null;

            throw_unless(is_string($path), RuntimeException::class, 'Foundation Theme marketplace screenshot path must be a string.');

            return $path;
        })
        ->values();

    expect($paths)->toHaveCount(11)
        ->and($paths->filter(fn (string $path): bool => str_starts_with($path, 'docs/screenshots/') && str_ends_with($path, '.png')))->toHaveCount(10)
        ->and($paths->filter(fn (string $path): bool => str_starts_with($path, 'docs/assets/marketplace/') && str_ends_with($path, '.svg')))->toHaveCount(0)
        ->and($paths)->toContain(
            'docs/screenshots/theme-foundation-settings-screen.png',
            'docs/screenshots/theme-foundation-settings-screen-dark.png',
            'docs/screenshots/foundation-homepage-layout.png',
            'docs/screenshots/foundation-standard-page-layout.png',
            'docs/screenshots/foundation-blog-article-layout.png',
            'docs/screenshots/foundation-listing-layout.png',
            'docs/screenshots/foundation-contact-form-layout.png',
            'docs/screenshots/foundation-search-results-layout.png',
            'docs/screenshots/foundation-events-layout.png',
            'docs/screenshots/foundation-membership-gate-layout.png',
        );

    foreach ($paths as $path) {
        expect(is_file(dirname(__DIR__, 2) . '/' . $path))->toBeTrue();
    }
});

it('declares generated Tailwind output review as a command report capture', function (): void {
    $screenshots = foundationThemeScreenshotsContract();
    $entry = collect(foundationThemeManifestList($screenshots, 'entries'))
        ->firstWhere('id', 'generated-tailwind-asset-output-review');

    throw_unless(is_array($entry), RuntimeException::class, 'Generated Tailwind output review screenshot entry must exist.');

    expect($entry)
        ->toMatchArray([
            'surface' => 'developer',
            'targetType' => 'console-command',
            'target' => 'capell:frontend-tailwind-assets --report',
            'reportPath' => 'packages/theme-foundation/docs/reports/generated-tailwind-asset-output-review.md',
            'screenshotPath' => 'packages/theme-foundation/docs/screenshots/generated-tailwind-asset-output-review.png',
            'darkScreenshotPath' => 'packages/theme-foundation/docs/screenshots/generated-tailwind-asset-output-review-dark.png',
        ]);

    $packageRoot = dirname(__DIR__, 2);
    $reportPath = $packageRoot . '/docs/reports/generated-tailwind-asset-output-review.md';

    expect($entry['notes'])
        ->toContain('not the generic settings screen')
        ->and(is_file($reportPath))->toBeTrue()
        ->and(is_file($packageRoot . '/docs/screenshots/generated-tailwind-asset-output-review.png'))->toBeTrue()
        ->and(is_file($packageRoot . '/docs/screenshots/generated-tailwind-asset-output-review-dark.png'))->toBeTrue();

    $report = file_get_contents($reportPath) ?: '';

    expect($report)
        ->toContain('capell:frontend-tailwind-assets --report')
        ->toContain('imports')
        ->toContain('plugins')
        ->toContain('sources')
        ->toContain('theme_colors');
});

it('declares standalone theme packages as frontend themes', function (string $packageDirectory, string $composerName, string $themeKey): void {
    $manifest = themePackageManifest($packageDirectory);
    $composer = themePackageComposer($packageDirectory);
    $requires = data_get($manifest, 'dependencies.requires');

    if (! is_array($requires)) {
        throw new RuntimeException('Theme package manifest dependencies.requires must be an array.');
    }

    expect($manifest['name'])->toBe($composerName)
        ->and($composer['name'])->toBe($composerName)
        ->and($manifest['kind'])->toBe('theme')
        ->and($manifest['themeKey'])->toBe($themeKey)
        ->and($manifest['extends'] ?? null)->toBeIn([null, 'default'])
        ->and($requires)->toContain('capell-app/core')
        ->and($requires)->toContain('capell-app/frontend')
        ->and($manifest['product']['group'])->toBeIn(['Capell Foundation', 'Capell Themes']);
})->with('standalone theme packages');

dataset('standalone theme packages', function (): array {
    $packagesDirectory = dirname(__DIR__, 3);
    $themeManifests = glob($packagesDirectory . '/theme-*/capell.json') ?: [];
    $themeManifests = array_filter(
        $themeManifests,
        fn (string $manifestPath): bool => basename(dirname($manifestPath)) !== 'theme-business-solutions',
    );

    sort($themeManifests);

    $packages = [];

    foreach ($themeManifests as $manifestPath) {
        $packageDirectory = basename(dirname($manifestPath));
        $manifest = themePackageManifest($packageDirectory);
        $themeKey = $manifest['themeKey'] ?? null;
        if (($manifest['kind'] ?? null) !== 'theme') {
            continue;
        }

        if (! is_string($themeKey)) {
            continue;
        }

        $packages[$themeKey] = [
            $packageDirectory,
            $manifest['name'],
            $themeKey,
        ];
    }

    return $packages;
});

it('agrees with docs/themes.json and ThemeDefinitionData for each standalone theme package', function (string $packageDirectory, string $composerName, string $themeKey): void {
    // Wave 1.4 — delegates the capell.json <-> docs/themes.json <->
    // ThemeDefinitionData <-> docs/screenshots.json cross-check to the
    // extracted ValidateThemeCatalogueEntryAction, the same Action
    // `capell:validate-themes` and `scripts/validate-themes.php` use, so this
    // suite and the command stay in lock-step from one source of truth.
    $packagesDirectory = dirname(__DIR__, 3);

    $result = ValidateThemeCatalogueEntryAction::run($packageDirectory, $packagesDirectory);
    $violations = themeCatalogueViolationsExcludingKnownScreenshotSurfaceGap($themeKey, $result->violations);

    expect($result->themeKey)->toBe($themeKey)
        ->and($violations)->toBe([], "Theme package \"{$composerName}\" failed ValidateThemeCatalogueEntryAction: " . implode(' ', $violations));
})->with('standalone theme packages');

/**
 * @return array<string, mixed>
 */
function themePackageManifest(string $packageDirectory): array
{
    return foundationThemeManifestMap(json_decode(
        (string) file_get_contents(dirname(__DIR__, 3) . '/' . $packageDirectory . '/capell.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    ));
}

/**
 * @return array<string, mixed>
 */
function foundationThemeScreenshotsContract(): array
{
    return foundationThemeManifestMap(json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/docs/screenshots.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    ));
}

/**
 * @return array<string, mixed>
 */
function themePackageComposer(string $packageDirectory): array
{
    return foundationThemeManifestMap(json_decode(
        (string) file_get_contents(dirname(__DIR__, 3) . '/' . $packageDirectory . '/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    ));
}

/**
 * @return array<string, mixed>
 */
function foundationThemeManifestMap(mixed $value): array
{
    throw_unless(is_array($value), RuntimeException::class, 'Expected Foundation Theme manifest map.');

    $map = [];

    foreach ($value as $key => $item) {
        if (is_string($key)) {
            $map[$key] = $item;
        }
    }

    return $map;
}

/**
 * @param  array<string, mixed>  $manifest
 * @return list<array<string, mixed>>
 */
function foundationThemeManifestList(array $manifest, string $key): array
{
    $items = $manifest[$key] ?? [];

    throw_unless(is_array($items), RuntimeException::class, 'Expected Foundation Theme manifest list.');

    $list = [];

    foreach ($items as $item) {
        if (is_array($item)) {
            $list[] = foundationThemeManifestMap($item);
        }
    }

    return $list;
}
