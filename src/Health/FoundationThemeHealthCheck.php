<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Console\Commands\ValidateThemesCommand;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Support\Assets\FoundationThemeAssetContributor;
use FilesystemIterator;
use Illuminate\Support\Collection;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class FoundationThemeHealthCheck implements ChecksExtensionHealth
{
    private const string PACKAGE_ROOT = __DIR__ . '/../..';

    /**
     * Packages Foundation Theme cannot render without.
     *
     * @var list<string>
     */
    private const array REQUIRED_INSTALLED_PACKAGES = [
        'capell-app/theme-foundation',
        'capell-app/frontend',
        'capell-app/layout-builder',
        'capell-app/navigation',
    ];

    /**
     * @var list<string>
     */
    private const array REQUIRED_SECTIONS = [
        'navigation',
        'hero',
        'features',
        'proof',
        'content-listing',
        'cta',
        'footer',
    ];

    /**
     * @var list<string>
     */
    private const array REQUIRED_VIEW_NAMES = [
        'capell-theme-foundation::theme.page',
        'capell-theme-foundation::theme.sections.navigation',
        'capell-theme-foundation::theme.sections.hero',
        'capell-theme-foundation::theme.sections.features',
        'capell-theme-foundation::theme.sections.proof',
        'capell-theme-foundation::theme.sections.content-listing',
        'capell-theme-foundation::theme.sections.cta',
        'capell-theme-foundation::theme.sections.footer',
        'capell-theme-foundation::components.app.head.tokens',
        'capell-theme-foundation::components.layout.main',
        'capell-theme-foundation::components.widget.wrapper',
    ];

    /**
     * @var list<string>
     */
    private const array REQUIRED_PACKAGE_FILES = [
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
    ];

    /**
     * @var list<string>
     */
    private const array REQUIRED_TAILWIND_IMPORTS = [
        'resources/css/theme-foundation.css',
        'resources/css/widgets/foundation-widgets.css',
        'tippy.js/dist/tippy.css',
        'swiper/css',
        'swiper/css/autoplay',
        'swiper/css/pagination',
        'swiper/css/navigation',
    ];

    public function __construct(private readonly string $packageRoot = self::PACKAGE_ROOT) {}

    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    /**
     * @return Collection<int, DoctorCheckResultData>
     */
    public static function runDiagnostics(): Collection
    {
        $check = new self;

        return collect([
            $check->packageInstallationCheck(),
            $check->manifestProviderCheck(),
            $check->themeStudioDefinitionCheck(),
            $check->themeViewsCheck(),
            $check->assetPipelineCheck(),
            $check->configAndTokensCheck(),
            $check->providerRegistrationsCheck(),
            $check->fleetCatalogueCoverageCheck(),
            $check->fleetDemoContentCoverageCheck(),
            $check->fleetScreenshotFreshnessCheck(),
        ]);
    }

    public static function passed(): bool
    {
        return self::runDiagnostics()
            ->every(static fn (DoctorCheckResultData $result): bool => $result->passed);
    }

    public function packageInstallationCheck(): DoctorCheckResultData
    {
        $missingPackages = $this->missingInstalledPackages();

        return new DoctorCheckResultData(
            label: 'Foundation Theme package installation',
            passed: $missingPackages === [],
            message: $missingPackages === []
                ? 'Foundation Theme and its required frontend, layout-builder, and navigation dependencies are installed.'
                : 'Missing installed packages: ' . implode(', ', $missingPackages) . '.',
            remediation: $missingPackages === []
                ? null
                : 'Install or enable Foundation Theme and its required Capell frontend dependencies before rendering this theme.',
        );
    }

    public function manifestProviderCheck(): DoctorCheckResultData
    {
        $issues = $this->manifestProviderIssues();

        return new DoctorCheckResultData(
            label: 'Foundation Theme manifest and provider contract',
            passed: $issues === [],
            message: $issues === []
                ? 'The manifest declares the expected theme key, dependencies, runtime provider, and critical health check.'
                : 'Manifest/provider issues: ' . implode(' ', $issues),
            remediation: $issues === []
                ? null
                : 'Update capell.json so Diagnostics, install, and Marketplace discovery match the shipped Foundation Theme provider.',
        );
    }

    public function themeStudioDefinitionCheck(): DoctorCheckResultData
    {
        $issues = $this->themeStudioDefinitionIssues();

        return new DoctorCheckResultData(
            label: 'Foundation Theme Studio definition',
            passed: $issues === [],
            message: $issues === []
                ? 'The Foundation Theme Studio definition is registered with the expected runtime, asset, preset, and section contract.'
                : 'Theme Studio definition issues: ' . implode(' ', $issues),
            remediation: $issues === []
                ? null
                : 'Ensure FoundationThemeServiceProvider boots while the package is installed and registers the default Theme Studio definition.',
        );
    }

    public function themeViewsCheck(): DoctorCheckResultData
    {
        $missingViews = $this->missingViews();

        return new DoctorCheckResultData(
            label: 'Foundation Theme render views',
            passed: $missingViews === [],
            message: $missingViews === []
                ? 'The Foundation layout, section, token, layout component, and widget wrapper views are resolvable.'
                : 'Missing Foundation Theme views: ' . implode(', ', $missingViews) . '.',
            remediation: $missingViews === []
                ? null
                : 'Ensure the Foundation Theme view namespace is loaded and required Blade files are present.',
        );
    }

    public function assetPipelineCheck(): DoctorCheckResultData
    {
        $missingAssets = $this->missingAssets();

        return new DoctorCheckResultData(
            label: 'Foundation Theme asset pipeline',
            passed: $missingAssets === [],
            message: $missingAssets === []
                ? 'The Foundation source assets, publishable build manifest, published public manifest, and vendor asset registrations are present.'
                : 'Missing Foundation Theme assets: ' . implode(', ', $missingAssets) . '.',
            remediation: $missingAssets === []
                ? null
                : 'Restore missing package assets, boot the Foundation Theme provider, then publish the frontend build assets.',
        );
    }

    public function configAndTokensCheck(): DoctorCheckResultData
    {
        $issues = $this->configAndTokenIssues();

        return new DoctorCheckResultData(
            label: 'Foundation Theme config and tokens',
            passed: $issues === [],
            message: $issues === []
                ? 'Foundation config, settings schema, runtime token settings, and token Blade hook are available.'
                : 'Config/token issues: ' . implode(' ', $issues),
            remediation: $issues === []
                ? null
                : 'Restore the Foundation config/settings classes and ensure the provider registers the theme_foundation settings group.',
        );
    }

    public function providerRegistrationsCheck(): DoctorCheckResultData
    {
        $issues = $this->providerRegistrationIssues();

        return new DoctorCheckResultData(
            label: 'Foundation Theme provider registrations',
            passed: $issues === [],
            message: $issues === []
                ? 'The provider registered the Tailwind generator, asset contributor, runtime asset condition, and settings surface.'
                : 'Provider registration issues: ' . implode(' ', $issues),
            remediation: $issues === []
                ? null
                : 'Boot FoundationThemeServiceProvider with the package installed so runtime services and registries are populated.',
        );
    }

    /**
     * Wave 2.8 — flags theme packages with no matching `docs/themes.json`
     * catalogue entry. The catalogue is the fleet's canonical list, so a
     * theme package present under `packages/theme-*` but absent from it is
     * a shipped-but-uncatalogued theme.
     */
    public function fleetCatalogueCoverageCheck(): DoctorCheckResultData
    {
        $issues = $this->themesMissingCatalogueEntries();

        return new DoctorCheckResultData(
            label: 'Fleet theme catalogue coverage',
            passed: $issues === [],
            message: $issues === []
                ? 'Every discovered theme package has a matching docs/themes.json catalogue entry.'
                : 'Themes missing a catalogue entry: ' . implode(', ', $issues) . '.',
            remediation: $issues === []
                ? null
                : 'Add a docs/themes.json entry for each listed theme package before it ships.',
        );
    }

    /**
     * Wave 2.8 — flags theme packages with no discoverable
     * `ProvidesThemeDemoContent` implementation under their own `src/`
     * directory, meaning the theme cannot pass the Wave 3 demo-content
     * contract.
     */
    public function fleetDemoContentCoverageCheck(): DoctorCheckResultData
    {
        $issues = $this->themesMissingDemoContent();

        return new DoctorCheckResultData(
            label: 'Fleet demo content coverage',
            passed: $issues === [],
            message: $issues === []
                ? 'Every discovered theme package registers a ProvidesThemeDemoContent implementation.'
                : 'Themes missing DemoContent: ' . implode(', ', $issues) . '.',
            remediation: $issues === []
                ? null
                : 'Implement Capell\\FoundationTheme\\Contracts\\ProvidesThemeDemoContent for each listed theme.',
        );
    }

    /**
     * Wave 2.8 — flags theme packages with no `docs/screenshots.json`
     * manifest, or one whose `entries` list is empty, meaning the theme has
     * no fresh screenshot evidence for the marketplace.
     */
    public function fleetScreenshotFreshnessCheck(): DoctorCheckResultData
    {
        $issues = $this->themesMissingScreenshots();

        return new DoctorCheckResultData(
            label: 'Fleet screenshot freshness',
            passed: $issues === [],
            message: $issues === []
                ? 'Every discovered theme package has a docs/screenshots.json manifest with at least one entry.'
                : 'Themes missing/stale screenshots: ' . implode(', ', $issues) . '.',
            remediation: $issues === []
                ? null
                : 'Capture and commit docs/screenshots.json entries for each listed theme before it ships.',
        );
    }

    /**
     * @return list<string>
     */
    public function themesMissingCatalogueEntries(): array
    {
        $packagesRoot = $this->packagesRoot();

        if ($packagesRoot === null) {
            return [];
        }

        $catalogueThemeKeys = $this->catalogueThemeKeys($packagesRoot);

        $missing = [];

        foreach ($this->themePackageDirectories($packagesRoot) as $packageDirectory) {
            $manifest = $this->readJsonFile($packagesRoot . '/' . $packageDirectory . '/capell.json');
            $themeKey = is_string($manifest['themeKey'] ?? null) ? $manifest['themeKey'] : $packageDirectory;

            if (! in_array($themeKey, $catalogueThemeKeys, true)) {
                $missing[] = $packageDirectory;
            }
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    public function themesMissingDemoContent(): array
    {
        $packagesRoot = $this->packagesRoot();

        if ($packagesRoot === null) {
            return [];
        }

        $missing = [];

        foreach ($this->themePackageDirectories($packagesRoot) as $packageDirectory) {
            // theme-foundation itself deliberately renders demo content via
            // the shared generic skeleton (ThemeDemoPageInstaller), not the
            // ProvidesThemeDemoContent contract, which exists precisely for
            // child themes that need bespoke, vertical-authentic content
            // instead of that skeleton — so it is not "missing" here.
            if ($packageDirectory === 'theme-foundation') {
                continue;
            }

            $sourceFiles = $this->phpFilesRecursively($packagesRoot . '/' . $packageDirectory . '/src');

            $implementsDemoContent = collect($sourceFiles)->contains(
                static function (string $filePath): bool {
                    $contents = file_get_contents($filePath) ?: '';

                    return str_contains($contents, 'ProvidesThemeDemoContent');
                },
            );

            if (! $implementsDemoContent) {
                $missing[] = $packageDirectory;
            }
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    public function themesMissingScreenshots(): array
    {
        $packagesRoot = $this->packagesRoot();

        if ($packagesRoot === null) {
            return [];
        }

        $missing = [];

        foreach ($this->themePackageDirectories($packagesRoot) as $packageDirectory) {
            $screenshotManifestPath = $packagesRoot . '/' . $packageDirectory . '/docs/screenshots.json';

            if (! is_file($screenshotManifestPath)) {
                $missing[] = $packageDirectory;

                continue;
            }

            $manifest = $this->readJsonFile($screenshotManifestPath);
            $entries = $manifest['entries'] ?? null;

            if (! is_array($entries) || $entries === []) {
                $missing[] = $packageDirectory;
            }
        }

        return $missing;
    }

    public function isThemeStudioDefinitionRegistered(): bool
    {
        if (! app()->bound(ThemeRegistry::class)) {
            return false;
        }

        return resolve(ThemeRegistry::class)->has(FoundationThemeServiceProvider::THEME_KEY);
    }

    /**
     * @return list<string>
     */
    public function missingInstalledPackages(): array
    {
        return array_values(collect(self::REQUIRED_INSTALLED_PACKAGES)
            ->reject(static fn (string $packageName): bool => CapellCore::isPackageInstalled($packageName))
            ->values()
            ->all());
    }

    public function publishedAssetManifestExists(): bool
    {
        return is_file(public_path('vendor/capell-theme-foundation/manifest.json'));
    }

    /**
     * @return list<string>
     */
    public function manifestProviderIssues(): array
    {
        $manifest = $this->manifest();

        if ($manifest === null) {
            return ['capell.json is missing or invalid.'];
        }

        $issues = [];

        if (($manifest['name'] ?? null) !== FoundationThemeServiceProvider::$packageName) {
            $issues[] = 'Package name does not match the service provider package name.';
        }

        if (($manifest['kind'] ?? null) !== 'theme') {
            $issues[] = 'Manifest kind must be theme.';
        }

        if (($manifest['capellApiVersion'] ?? null) !== self::compatibleCapellApiVersion()) {
            $issues[] = 'Manifest Capell API version does not match the health check.';
        }

        if (($manifest['themeKey'] ?? null) !== FoundationThemeServiceProvider::THEME_KEY) {
            $issues[] = 'Theme key does not match the service provider theme key.';
        }

        $surfaces = $manifest['surfaces'] ?? [];

        if (! is_array($surfaces) || ! in_array('admin', $surfaces, true) || ! in_array('frontend', $surfaces, true)) {
            $issues[] = 'Admin and frontend surfaces must be declared.';
        }

        $requiredPackages = $manifest['dependencies']['requires'] ?? [];

        if (! is_array($requiredPackages) || collect(['capell-app/frontend', 'capell-app/layout-builder', 'capell-app/navigation'])->diff($requiredPackages)->isNotEmpty()) {
            $issues[] = 'Manifest dependencies must require frontend, layout-builder, and navigation.';
        }

        $runtimeProviders = $manifest['providers']['runtime'] ?? [];

        if (! is_array($runtimeProviders) || $runtimeProviders !== [FoundationThemeServiceProvider::class]) {
            $issues[] = 'Runtime providers must contain only FoundationThemeServiceProvider.';
        }

        $healthChecks = is_array($manifest['healthChecks'] ?? null) ? $manifest['healthChecks'] : [];
        $healthCheck = collect($healthChecks)->first(
            static fn (mixed $check): bool => is_array($check)
                && ($check['class'] ?? null) === self::class,
        );

        if (! is_array($healthCheck)) {
            $issues[] = 'Health check class is not declared.';
        } elseif (($healthCheck['severity'] ?? null) !== 'critical') {
            $issues[] = 'Health check severity must remain critical.';
        }

        if (($manifest['commands']['setup'] ?? null) !== 'capell:theme-foundation-setup') {
            $issues[] = 'Setup command is not declared.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    public function themeStudioDefinitionIssues(): array
    {
        if (! $this->isThemeStudioDefinitionRegistered()) {
            return ['Foundation Theme Studio definition is not registered.'];
        }

        $definition = resolve(ThemeRegistry::class)->definition(FoundationThemeServiceProvider::THEME_KEY);
        $issues = [];

        if ($definition->package !== FoundationThemeServiceProvider::$packageName) {
            $issues[] = 'Registered definition package does not match Foundation Theme.';
        }

        if ($definition->runtime !== FrontendRuntime::Blade) {
            $issues[] = 'Registered definition must use the Blade runtime.';
        }

        if (($definition->assets['css'] ?? null) !== 'vendor/capell-theme-foundation/theme-foundation.css') {
            $issues[] = 'Registered definition CSS asset is missing.';
        }

        if ($definition->presets === []) {
            $issues[] = 'Registered definition has no presets.';
        }

        $missingSections = array_values(collect(self::REQUIRED_SECTIONS)
            ->diff($definition->includedSections)
            ->values()
            ->all());

        if ($missingSections !== []) {
            $issues[] = 'Missing definition sections: ' . implode(', ', $missingSections) . '.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    public function missingViews(): array
    {
        if (! function_exists('view')) {
            return self::REQUIRED_VIEW_NAMES;
        }

        return array_values(collect(self::REQUIRED_VIEW_NAMES)
            ->reject(static fn (string $viewName): bool => view()->exists($viewName))
            ->values()
            ->all());
    }

    /**
     * @return list<string>
     */
    public function missingAssets(): array
    {
        $missingAssets = $this->missingPackageFiles();

        if (! $this->publishedAssetManifestExists()) {
            $missingAssets[] = 'public/vendor/capell-theme-foundation/manifest.json';
        }

        $tailwindImports = CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindImport);
        $tailwindSources = CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindSource);

        foreach (self::REQUIRED_TAILWIND_IMPORTS as $import) {
            $hasImport = $tailwindImports->contains(
                static fn (mixed $asset): bool => $asset->packageName === FoundationThemeServiceProvider::$packageName
                    && $asset->value === $import,
            );

            if (! $hasImport) {
                $missingAssets[] = 'Tailwind import registration: ' . $import;
            }
        }

        $hasBladeSource = $tailwindSources->contains(
            static fn (mixed $asset): bool => $asset->packageName === FoundationThemeServiceProvider::$packageName
                && $asset->value === 'resources/views/**/*.blade.php',
        );

        if (! $hasBladeSource) {
            $missingAssets[] = 'Tailwind Blade source registration';
        }

        return array_values($missingAssets);
    }

    /**
     * @return list<string>
     */
    public function missingPackageFiles(): array
    {
        $packageRoot = $this->packageRoot;

        return array_values(collect(self::REQUIRED_PACKAGE_FILES)
            ->reject(static fn (string $relativePath): bool => is_file($packageRoot . '/' . $relativePath))
            ->values()
            ->all());
    }

    /**
     * @return list<string>
     */
    public function configAndTokenIssues(): array
    {
        $issues = [];

        if (! is_string(config('capell-theme-foundation.asset_build_tool')) || config('capell-theme-foundation.asset_build_tool') === '') {
            $issues[] = 'asset_build_tool config is missing.';
        }

        $tailwindConfig = config('capell-theme-foundation.tailwind');

        if (! is_array($tailwindConfig) || ! is_string($tailwindConfig['output_css'] ?? null) || $tailwindConfig['output_css'] === '') {
            $issues[] = 'Tailwind output_css config is missing.';
        }

        $npmDependencies = config('capell-theme-foundation.npm_dependencies');

        if (! is_array($npmDependencies) || ! isset($npmDependencies['tailwindcss'], $npmDependencies['swiper'])) {
            $issues[] = 'Required npm dependency config is missing.';
        }

        if (FoundationThemeSettings::group() !== 'theme_foundation') {
            $issues[] = 'Foundation settings group is incorrect.';
        }

        if (FoundationThemeSettings::schema() !== FoundationThemeSettingsSchema::class) {
            $issues[] = 'Foundation settings schema link is incorrect.';
        }

        if (
            FoundationThemeSettings::sectionSpacingCssValueFor('relaxed') === ''
            || FoundationThemeSettings::widgetGapCssValueFor('balanced') === ''
        ) {
            $issues[] = 'Foundation spacing token defaults are missing.';
        }

        if (app()->bound(SettingsSchemaRegistry::class)) {
            $registry = resolve(SettingsSchemaRegistry::class);

            if ($registry->getSettingsClass('theme_foundation') !== FoundationThemeSettings::class) {
                $issues[] = 'Foundation settings class is not registered.';
            }

            if (! in_array(FoundationThemeSettingsSchema::class, $registry->getSchemas('theme_foundation'), true)) {
                $issues[] = 'Foundation settings schema is not registered.';
            }
        } else {
            $issues[] = 'Settings schema registry is not bound.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    public function providerRegistrationIssues(): array
    {
        $issues = [];

        if (! app()->bound('capell.tailwind.generator')) {
            $issues[] = 'Tailwind generator binding is missing.';
        }

        if (! app()->bound(FoundationThemeAssetContributor::class)) {
            $issues[] = 'Foundation asset contributor binding is missing.';
        }

        return $issues;
    }

    /**
     * Locates the `packages/` monorepo root from this package's own root, so
     * the fleet-wide Wave 2.8 checks can walk sibling `theme-*` packages.
     * Mirrors {@see ValidateThemesCommand::packagesRoot()}.
     */
    private function packagesRoot(): ?string
    {
        $candidate = dirname($this->packageRoot);

        if (is_dir($candidate) && glob($candidate . '/theme-*') !== []) {
            return $candidate;
        }

        if (function_exists('base_path')) {
            $fallback = base_path('packages');

            if (is_dir($fallback)) {
                return $fallback;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function themePackageDirectories(string $packagesRoot): array
    {
        $manifestPaths = glob($packagesRoot . '/theme-*/capell.json') ?: [];
        sort($manifestPaths);

        $directories = [];

        foreach ($manifestPaths as $manifestPath) {
            $manifest = $this->readJsonFile($manifestPath);

            if (($manifest['kind'] ?? null) !== 'theme') {
                continue;
            }

            $directories[] = basename(dirname($manifestPath));
        }

        return $directories;
    }

    /**
     * @return list<string>
     */
    private function catalogueThemeKeys(string $packagesRoot): array
    {
        $cataloguePath = dirname($packagesRoot) . '/docs/themes.json';
        $catalogue = $this->readJsonFile($cataloguePath);
        $themes = $catalogue['themes'] ?? [];

        if (! is_array($themes)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $theme): ?string => is_array($theme) && is_string($theme['themeKey'] ?? null) ? $theme['themeKey'] : null,
            $themes,
        )));
    }

    /**
     * @return list<string>
     */
    private function phpFilesRecursively(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        $phpFiles = [];

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        try {
            $contents = file_get_contents($path);

            if ($contents === false) {
                return [];
            }

            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function manifest(): ?array
    {
        $manifestPath = $this->packageRoot . '/capell.json';

        if (! is_file($manifestPath)) {
            return null;
        }

        try {
            $contents = file_get_contents($manifestPath);

            if ($contents === false) {
                return null;
            }

            $manifest = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($manifest) ? $manifest : null;
    }
}
