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
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Support\Assets\FoundationThemeAssetContributor;
use Illuminate\Support\Collection;
use JsonException;

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
        return '^4.0';
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
                ? 'Foundation Theme and its required frontend/layout-builder dependencies are installed.'
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

        if (! is_array($requiredPackages) || collect(['capell-app/frontend', 'capell-app/layout-builder'])->diff($requiredPackages)->isNotEmpty()) {
            $issues[] = 'Manifest dependencies must require frontend and layout-builder.';
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

        $buildAssets = CapellCore::getVendorAssetsForType(VendorAssetEnum::BuildAsset);
        $tailwindImports = CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindImport);
        $tailwindSources = CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindSource);

        $hasBuildAsset = $buildAssets->contains(
            static fn (mixed $asset): bool => $asset->packageName === FoundationThemeServiceProvider::$packageName
                && $asset->value === 'vendor/capell-theme-foundation'
                && $asset->secondaryValue === 'resources/js/capell-frontend.js'
                && $asset->condition === 'theme-foundation-runtime',
        );

        if (! $hasBuildAsset) {
            $missingAssets[] = 'build asset registration';
        }

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

        $hasRuntimeCondition = collect(CapellCore::getVendorAssetsForType(VendorAssetEnum::BuildAsset))->contains(
            static fn (mixed $asset): bool => $asset->packageName === FoundationThemeServiceProvider::$packageName
                && $asset->condition === 'theme-foundation-runtime',
        );

        if (! $hasRuntimeCondition) {
            $issues[] = 'Foundation runtime vendor asset condition is missing.';
        }

        return $issues;
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
