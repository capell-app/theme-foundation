<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\GenerateThemeScaffoldAction;
use Capell\FoundationTheme\Data\ThemeScaffoldRequestData;
use Illuminate\Support\Facades\File;

/**
 * Exercises `capell:make-theme`'s generator (see
 * `GenerateThemeScaffoldAction`) end to end, at the Action layer rather than
 * through the Artisan command, so the test stays fast and does not depend on
 * interactive prompts. Every scaffold is written under a fresh temp
 * directory rather than the real monorepo `packages/` tree, and that temp
 * directory is always removed afterwards, even on assertion failure.
 */
beforeEach(function (): void {
    $this->scaffoldBasePath = sys_get_temp_dir() . '/capell-make-theme-test-' . uniqid('', true);

    File::ensureDirectoryExists($this->scaffoldBasePath);
});

afterEach(function (): void {
    if (isset($this->scaffoldBasePath) && File::isDirectory($this->scaffoldBasePath)) {
        File::deleteDirectory($this->scaffoldBasePath);
    }
});

it('generates a complete theme scaffold with correct manifest, namespace, and provider wiring', function (): void {
    $request = new ThemeScaffoldRequestData(
        themeSlug: 'business',
        displayName: 'Business',
        tier: 'premium',
        family: 'service-business',
        basePackagesPath: $this->scaffoldBasePath,
    );

    $writtenFiles = GenerateThemeScaffoldAction::run($request);

    expect($writtenFiles)->not->toBeEmpty();

    foreach ($writtenFiles as $writtenFile) {
        expect($writtenFile)->toBeFile();
    }

    $packageDirectory = $this->scaffoldBasePath . '/theme-business';

    $manifest = decodeThemeScaffoldJson($packageDirectory . '/capell.json');

    expect($manifest['themeKey'])->toBe('business')
        ->and($manifest['extends'])->toBe('default')
        ->and($manifest['kind'])->toBe('theme')
        ->and($manifest['name'])->toBe('capell-app/theme-business')
        ->and(data_get($manifest, 'product.tier'))->toBe('premium')
        ->and(data_get($manifest, 'providers.runtime'))->toContain('Capell\\ThemeBusiness\\BusinessThemeServiceProvider');

    $composerJson = decodeThemeScaffoldJson($packageDirectory . '/composer.json');

    expect($composerJson['name'])->toBe('capell-app/theme-business')
        ->and(data_get($composerJson, 'autoload.psr-4'))->toHaveKey('Capell\\ThemeBusiness\\');

    $serviceProviderContents = (string) file_get_contents(
        $packageDirectory . '/src/BusinessThemeServiceProvider.php',
    );

    expect($serviceProviderContents)
        ->toContain('declare(strict_types=1);')
        ->toContain('namespace Capell\\ThemeBusiness;')
        ->toContain('use Capell\\FoundationTheme\\Support\\Providers\\RegistersLayoutNativeThemeDefaults;')
        ->toContain('final class BusinessThemeServiceProvider extends ServiceProvider')
        ->toContain("THEME_KEY = 'business';");

    $demoContentContents = (string) file_get_contents(
        $packageDirectory . '/src/Support/Demo/BusinessDemoContent.php',
    );

    expect($demoContentContents)
        ->toContain('implements ProvidesThemeDemoContent')
        ->toContain("'homepage'")
        ->toContain("'directory'")
        ->toContain("'detail'")
        ->toContain("'contact'")
        ->toContain("'empty'")
        ->toContain("'not-found'")
        ->toContain("'cta'");

    $installActionContents = (string) file_get_contents(
        $packageDirectory . '/src/Actions/InstallBusinessThemeDemoAction.php',
    );

    expect($installActionContents)
        ->toContain('implements InstallsThemeDemo')
        ->toContain('public function handle(ThemeDemoInstallData $data): int');

    $demoCommandContents = (string) file_get_contents(
        $packageDirectory . '/src/Console/Commands/BusinessDemoCommand.php',
    );

    expect($demoCommandContents)->toContain("'capell:theme-business-demo");

    $screenshotsManifest = decodeThemeScaffoldJson($packageDirectory . '/docs/screenshots.json');

    $screenshotEntries = $screenshotsManifest['entries'] ?? [];
    $screenshotSurfaceIds = is_array($screenshotEntries) ? array_column($screenshotEntries, 'id') : [];

    expect($screenshotSurfaceIds)->toContain(
        'business-homepage',
        'business-directory',
        'business-detail',
        'business-contact',
        'business-empty',
        'business-not-found',
        'business-cta',
    );

    expect($packageDirectory . '/tests/Pest.php')->toBeFile()
        ->and($packageDirectory . '/tests/Unit/PublicOutputSafetyTest.php')->toBeFile()
        ->and($packageDirectory . '/tests/Unit/ManifestTest.php')->toBeFile()
        ->and($packageDirectory . '/tests/Unit/DefinitionTest.php')->toBeFile();

    $publicOutputSafetyTestContents = (string) file_get_contents(
        $packageDirectory . '/tests/Unit/PublicOutputSafetyTest.php',
    );

    expect($publicOutputSafetyTestContents)->toContain(
        'Capell\\FoundationTheme\\Testing\\AssertsPublicThemeOutputSafety',
    );
});

it('rejects an invalid theme slug before writing anything', function (): void {
    expect(fn (): ThemeScaffoldRequestData => new ThemeScaffoldRequestData(
        themeSlug: 'Not A Slug!',
        displayName: 'Broken',
        tier: 'premium',
        family: 'service-business',
        basePackagesPath: $this->scaffoldBasePath,
    ))->toThrow(InvalidArgumentException::class);
});

it('rejects an unknown tier', function (): void {
    expect(fn (): ThemeScaffoldRequestData => new ThemeScaffoldRequestData(
        themeSlug: 'business',
        displayName: 'Business',
        tier: 'enterprise',
        family: 'service-business',
        basePackagesPath: $this->scaffoldBasePath,
    ))->toThrow(InvalidArgumentException::class);
});

it('scaffolds a free-tier theme into its own package directory without touching siblings', function (): void {
    $firstRequest = new ThemeScaffoldRequestData(
        themeSlug: 'docs',
        displayName: 'Docs',
        tier: 'free',
        family: 'docs-knowledge',
        basePackagesPath: $this->scaffoldBasePath,
    );

    GenerateThemeScaffoldAction::run($firstRequest);

    $manifest = decodeThemeScaffoldJson($this->scaffoldBasePath . '/theme-docs/capell.json');

    expect(data_get($manifest, 'product.tier'))->toBe('free')
        ->and($manifest['themeKey'])->toBe('docs');

    expect($this->scaffoldBasePath . '/theme-business')->not->toBeDirectory();
});

/** @return array<string, mixed> */
function decodeThemeScaffoldJson(string $path): array
{
    $contents = file_get_contents($path);
    $decoded = is_string($contents) ? json_decode($contents, true, flags: JSON_THROW_ON_ERROR) : null;

    if (! is_array($decoded)) {
        throw new RuntimeException("Expected JSON object at {$path}.");
    }

    $map = [];

    foreach ($decoded as $key => $value) {
        if (is_string($key)) {
            $map[$key] = $value;
        }
    }

    return $map;
}
