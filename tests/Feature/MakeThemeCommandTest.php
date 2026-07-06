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
        themeSlug: 'call-out',
        displayName: 'Call Out',
        tier: 'premium',
        family: 'service-business',
        basePackagesPath: $this->scaffoldBasePath,
    );

    $writtenFiles = GenerateThemeScaffoldAction::run($request);

    expect($writtenFiles)->not->toBeEmpty();

    foreach ($writtenFiles as $writtenFile) {
        expect($writtenFile)->toBeFile();
    }

    $packageDirectory = $this->scaffoldBasePath . '/theme-call-out';

    $manifest = json_decode(
        (string) file_get_contents($packageDirectory . '/capell.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest['themeKey'])->toBe('call-out')
        ->and($manifest['extends'])->toBe('default')
        ->and($manifest['kind'])->toBe('theme')
        ->and($manifest['name'])->toBe('capell-app/theme-call-out')
        ->and($manifest['product']['tier'])->toBe('premium')
        ->and($manifest['providers']['runtime'])->toContain('Capell\\ThemeStudio\\CallOut\\CallOutThemeServiceProvider');

    $composerJson = json_decode(
        (string) file_get_contents($packageDirectory . '/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($composerJson['name'])->toBe('capell-app/theme-call-out')
        ->and($composerJson['autoload']['psr-4'])->toHaveKey('Capell\\ThemeStudio\\CallOut\\');

    $serviceProviderContents = (string) file_get_contents(
        $packageDirectory . '/src/CallOutThemeServiceProvider.php',
    );

    expect($serviceProviderContents)
        ->toContain('declare(strict_types=1);')
        ->toContain('namespace Capell\\ThemeStudio\\CallOut;')
        ->toContain('use Capell\\FoundationTheme\\Support\\Providers\\RegistersLayoutNativeThemeDefaults;')
        ->toContain('final class CallOutThemeServiceProvider extends ServiceProvider')
        ->toContain("THEME_KEY = 'call-out';");

    $demoContentContents = (string) file_get_contents(
        $packageDirectory . '/src/Support/Demo/CallOutDemoContent.php',
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
        $packageDirectory . '/src/Actions/InstallCallOutThemeDemoAction.php',
    );

    expect($installActionContents)
        ->toContain('implements InstallsThemeDemo')
        ->toContain('public function handle(ThemeDemoInstallData $data): int');

    $demoCommandContents = (string) file_get_contents(
        $packageDirectory . '/src/Console/Commands/CallOutDemoCommand.php',
    );

    expect($demoCommandContents)->toContain("'capell:theme-call-out-demo");

    $screenshotsManifest = json_decode(
        (string) file_get_contents($packageDirectory . '/docs/screenshots.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $screenshotSurfaceIds = array_column($screenshotsManifest['entries'], 'id');

    expect($screenshotSurfaceIds)->toContain(
        'call-out-homepage',
        'call-out-directory',
        'call-out-detail',
        'call-out-contact',
        'call-out-empty',
        'call-out-not-found',
        'call-out-cta',
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
        themeSlug: 'call-out',
        displayName: 'Call Out',
        tier: 'enterprise',
        family: 'service-business',
        basePackagesPath: $this->scaffoldBasePath,
    ))->toThrow(InvalidArgumentException::class);
});

it('scaffolds a free-tier theme into its own package directory without touching siblings', function (): void {
    $firstRequest = new ThemeScaffoldRequestData(
        themeSlug: 'reading-room',
        displayName: 'Reading Room',
        tier: 'free',
        family: 'docs-knowledge',
        basePackagesPath: $this->scaffoldBasePath,
    );

    GenerateThemeScaffoldAction::run($firstRequest);

    $manifest = json_decode(
        (string) file_get_contents($this->scaffoldBasePath . '/theme-reading-room/capell.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest['product']['tier'])->toBe('free')
        ->and($manifest['themeKey'])->toBe('reading-room');

    expect($this->scaffoldBasePath . '/theme-call-out')->not->toBeDirectory();
});
