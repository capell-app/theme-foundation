<?php

declare(strict_types=1);

use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Health\FoundationThemeHealthCheck;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;

beforeEach(function (): void {
    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);
    CapellCore::forcePackageInstalled('capell-app/frontend');
    CapellCore::forcePackageInstalled('capell-app/layout-builder');
    foundationThemeRegisterInstalledHealthSurfaces();

    $this->publishedManifestPath = public_path('vendor/capell-theme-foundation/manifest.json');

    if (! is_dir(dirname($this->publishedManifestPath))) {
        mkdir(dirname($this->publishedManifestPath), 0o775, true);
    }

    file_put_contents($this->publishedManifestPath, '{}');
});

afterEach(function (): void {
    if (is_file($this->publishedManifestPath)) {
        unlink($this->publishedManifestPath);
    }

    foundationThemeDeleteDirectory(sys_get_temp_dir() . '/capell-theme-foundation-health-manifest-missing');
    foundationThemeDeleteDirectory(sys_get_temp_dir() . '/capell-theme-foundation-health-asset-missing');

    resolve(ThemeRegistry::class)->reset();
});

it('reports a compatible capell api version', function (): void {
    expect(FoundationThemeHealthCheck::compatibleCapellApiVersion())->toBe('^4.0');
});

it('runs real diagnostics returning check results', function (): void {
    $results = FoundationThemeHealthCheck::runDiagnostics();

    expect($results)->toHaveCount(7)
        ->and($results->every(static fn (mixed $result): bool => $result instanceof DoctorCheckResultData))->toBeTrue()
        ->and($results->pluck('label')->all())->toBe([
            'Foundation Theme package installation',
            'Foundation Theme manifest and provider contract',
            'Foundation Theme Studio definition',
            'Foundation Theme render views',
            'Foundation Theme asset pipeline',
            'Foundation Theme config and tokens',
            'Foundation Theme provider registrations',
        ]);
});

it('passes when required package health surfaces are present', function (): void {
    $results = FoundationThemeHealthCheck::runDiagnostics();

    expect(FoundationThemeHealthCheck::passed())->toBeTrue()
        ->and($results->every(static fn (DoctorCheckResultData $result): bool => $result->passed))->toBeTrue();
});

it('fails the theme definition check when the definition is not registered', function (): void {
    resolve(ThemeRegistry::class)->reset();

    $check = new FoundationThemeHealthCheck;

    expect($check->isThemeStudioDefinitionRegistered())->toBeFalse()
        ->and($check->themeStudioDefinitionCheck()->passed)->toBeFalse()
        ->and($check->themeStudioDefinitionIssues())->toContain('Foundation Theme Studio definition is not registered.')
        ->and(FoundationThemeHealthCheck::passed())->toBeFalse();
});

it('fails the package installation check when a dependency is missing', function (): void {
    CapellCore::forcePackageInstalled('capell-app/layout-builder', false);

    $check = new FoundationThemeHealthCheck;

    expect($check->missingInstalledPackages())->toContain('capell-app/layout-builder')
        ->and($check->packageInstallationCheck()->passed)->toBeFalse()
        ->and(FoundationThemeHealthCheck::passed())->toBeFalse();
});

it('fails the published assets check when the manifest is missing', function (): void {
    unlink($this->publishedManifestPath);

    $check = new FoundationThemeHealthCheck;

    expect($check->publishedAssetManifestExists())->toBeFalse()
        ->and($check->assetPipelineCheck()->passed)->toBeFalse()
        ->and(FoundationThemeHealthCheck::passed())->toBeFalse();
});

it('fails the manifest provider check when capell json is missing', function (): void {
    $packageRoot = foundationThemeTemporaryPackageRoot('manifest-missing');
    unlink($packageRoot . '/capell.json');

    $check = new FoundationThemeHealthCheck($packageRoot);

    expect($check->manifestProviderCheck()->passed)->toBeFalse()
        ->and($check->manifestProviderIssues())->toContain('capell.json is missing or invalid.');
});

it('fails the render views check when required views are not resolvable', function (): void {
    view()->replaceNamespace('capell-theme-foundation', sys_get_temp_dir());

    $check = new FoundationThemeHealthCheck;

    expect($check->themeViewsCheck()->passed)->toBeFalse()
        ->and($check->missingViews())->toContain('capell-theme-foundation::theme.page');
});

it('fails the asset pipeline check when a required package asset is missing', function (): void {
    $packageRoot = foundationThemeTemporaryPackageRoot('asset-missing');
    unlink($packageRoot . '/resources/css/theme-foundation.css');

    $check = new FoundationThemeHealthCheck($packageRoot);

    expect($check->assetPipelineCheck()->passed)->toBeFalse()
        ->and($check->missingAssets())->toContain('resources/css/theme-foundation.css');
});

it('fails the config and token check when required config is absent', function (): void {
    config([
        'capell-theme-foundation.asset_build_tool' => null,
        'capell-theme-foundation.tailwind' => [],
    ]);

    $check = new FoundationThemeHealthCheck;

    expect($check->configAndTokensCheck()->passed)->toBeFalse()
        ->and($check->configAndTokenIssues())->toContain('asset_build_tool config is missing.')
        ->and($check->configAndTokenIssues())->toContain('Tailwind output_css config is missing.');
});

function foundationThemeRegisterInstalledHealthSurfaces(): void
{
    $provider = new FoundationThemeServiceProvider(app());

    foreach ([
        'registerVendorCssJsAssets',
        'registerSettingsSchemas',
        'registerThemeStudioDefinition',
    ] as $methodName) {
        $method = new ReflectionMethod(FoundationThemeServiceProvider::class, $methodName);
        $method->invoke($provider);
    }
}

function foundationThemeTemporaryPackageRoot(string $name): string
{
    $root = sys_get_temp_dir() . '/capell-theme-foundation-health-' . $name;
    foundationThemeDeleteDirectory($root);

    foreach ([
        'capell.json',
        'config/capell-theme-foundation.php',
        'publishes/build/manifest.json',
        'resources/css/theme-foundation.css',
        'resources/css/widgets/foundation-widgets.css',
        'resources/js/capell-frontend.js',
        'resources/views/components/app/head/tokens.blade.php',
        'resources/views/theme/page.blade.php',
        'resources/views/theme/sections/navigation.blade.php',
        'resources/views/theme/sections/hero.blade.php',
        'resources/views/theme/sections/features.blade.php',
        'resources/views/theme/sections/proof.blade.php',
        'resources/views/theme/sections/content-listing.blade.php',
        'resources/views/theme/sections/cta.blade.php',
        'resources/views/theme/sections/footer.blade.php',
    ] as $relativePath) {
        $sourcePath = dirname(__DIR__, 2) . '/' . $relativePath;
        $targetPath = $root . '/' . $relativePath;

        if (! is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0o775, true);
        }

        copy($sourcePath, $targetPath);
    }

    return $root;
}

function foundationThemeDeleteDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($path);
}
