<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Providers;

use Capell\Admin\Data\Extensions\ExtensionManagementSurfaceData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\BlueprintGroupEnum;
use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Assets\VendorAssetConditionRegistry;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Themes\ThemeChromeRegistry;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePresetData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Actions\ResolveFoundationThemeTokensAction;
use Capell\FoundationTheme\Console\Commands\DemoCommand;
use Capell\FoundationTheme\Console\Commands\MakeThemeCommand;
use Capell\FoundationTheme\Console\Commands\SetupCommand;
use Capell\FoundationTheme\Console\Commands\ThemeCatalogueReportCommand;
use Capell\FoundationTheme\Console\Commands\ValidateThemesCommand;
use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Capell\FoundationTheme\Enums\FoundationThemeAssetEnum;
use Capell\FoundationTheme\Filament\Extenders\FoundationLayoutContainerSchemaExtender;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Capell\FoundationTheme\Listeners\RunTailwindAssetsOnPackageChange;
use Capell\FoundationTheme\Livewire\Assets\Table\PageAssets;
use Capell\FoundationTheme\Livewire\Widget\Pages;
use Capell\FoundationTheme\Rendering\ChromeSplitBladeThemeRenderer;
use Capell\FoundationTheme\Rendering\VariantViewSectionRenderer;
use Capell\FoundationTheme\Rendering\VariantViewSectionRenderer as ViewSectionRenderer;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Support\Assets\FoundationThemeAssetContributor;
use Capell\FoundationTheme\Support\Blade\BladeDirectives;
use Capell\FoundationTheme\Support\CapellOptionalExtensionAvailability;
use Capell\FoundationTheme\Support\FoundationThemeRuntimeManifestContributor;
use Capell\FoundationTheme\Support\Interceptors\Themes\FoundationThemeInterceptor;
use Capell\FoundationTheme\Support\Media\CapellUrlGenerator;
use Capell\FoundationTheme\View\Components\Actions as ActionsComponent;
use Capell\FoundationTheme\View\Components\App\Body as AppBodyComponent;
use Capell\FoundationTheme\View\Components\Footer\Index as FooterIndexComponent;
use Capell\FoundationTheme\View\Components\Layout\Index as LayoutIndexComponent;
use Capell\FoundationTheme\View\Components\Layout\Main as LayoutMainComponent;
use Capell\FoundationTheme\View\Components\Media\Svg;
use Capell\FoundationTheme\View\Components\NewsletterForm;
use Capell\FoundationTheme\View\Components\ThemeFormEmbed;
use Capell\FoundationTheme\View\Components\Widget\Page\Breadcrumbs as PageBreadcrumbsComponent;
use Capell\FoundationTheme\View\Components\Widget\Page\Children as PageChildrenComponent;
use Capell\FoundationTheme\View\Components\Widget\Page\Content as PageContentComponent;
use Capell\FoundationTheme\View\Components\Widget\Page\Latest as PageLatestComponent;
use Capell\FoundationTheme\View\Components\Widget\Page\Siblings as PageSiblingsComponent;
use Capell\FoundationTheme\View\Components\Widget\Slot as SlotComponent;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Contracts\FrontendAssetContributor;
use Capell\Frontend\Contracts\FrontendComponentRegistryInterface;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Data\FrontendAssetContextData;
use Capell\Frontend\Data\FrontendAssetData;
use Capell\Frontend\Events\FrontendContextResolved;
use Capell\Frontend\Events\FrontendRenderPreparing;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\Loader\SiteLoader;
use Capell\LayoutBuilder\Contracts\Extenders\LayoutContainerSchemaExtender;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Override;
use Spatie\LaravelPackageTools\Package;

final class FoundationThemeServiceProvider extends AbstractPackageServiceProvider
{
    public const string THEME_KEY = 'default';

    public static string $name = 'capell-theme-foundation';

    public static string $packageName = 'capell-app/theme-foundation';

    public static PackageTypeEnum $type = PackageTypeEnum::Theme;

    public static function definition(): ThemeDefinitionData
    {
        return new ThemeDefinitionData(
            key: self::THEME_KEY,
            name: 'Foundation',
            description: 'Clean starter theme for structured Capell sites, content previews, and shared child-theme defaults.',
            package: self::$packageName,
            previewImage: '/vendor/capell/themes/foundation.png',
            tags: ['Foundation', 'Structured', 'Default'],
            bestFit: ['Starter sites', 'Documentation', 'General publishing'],
            includedSections: ['navigation', 'hero', 'features', 'proof', 'content-listing', 'search', 'pagination', 'form', 'contact-split', 'cta', 'footer'],
            presets: [
                new ThemePresetData(
                    key: 'default',
                    name: 'Foundation',
                    description: 'Balanced neutral defaults with clear hierarchy and quiet content surfaces.',
                    previewImage: '/vendor/capell/themes/foundation.png',
                    values: [
                        'primaryColor' => '#315f8f',
                        'accentColor' => '#7c5f3f',
                        'neutralColor' => '#1f2937',
                        'surfaceColor' => '#faf9f7',
                        'foregroundColor' => '#111827',
                        'headingFont' => 'inter',
                        'bodyFont' => 'inter',
                        'spacing' => 'balanced',
                        'cardStyle' => 'flat',
                        'layoutPresentation' => 'structured',
                        'motionIntensity' => 'subtle',
                        'mediaTreatment' => 'natural',
                        'radius' => 'md',
                        'headingScale' => 'balanced',
                        'cardDensity' => 'comfortable',
                    ],
                ),
            ],
            assets: ['css' => 'vendor/capell-theme-foundation/theme-foundation.css'],
            runtime: FrontendRuntime::Blade,
            frontend: [
                'sectionVariants' => [
                    'hero' => ['default', 'split', 'stacked', 'full-bleed'],
                    'content-listing' => ['default', 'grid', 'rows', 'masonry-safe'],
                    'cta' => ['default', 'band', 'card', 'inline'],
                    'form' => ['default', 'encouraging'],
                    'pricing-value-spectrum' => ['default', 'compact'],
                    'faq-search-discovery' => ['default', 'categorised'],
                    'changelog-stream' => ['default', 'grid'],
                    'stats-display-band' => ['default', 'light'],
                ],
            ],
        );
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
                SetupCommand::class,
                ThemeCatalogueReportCommand::class,
                MakeThemeCommand::class,
                ValidateThemesCommand::class,
            ]);
    }

    public function packageBooted(): void
    {
        $this->registerBladeDirectives();
        $this->registerBladeComponents();
        $this->registerLayoutBuilderRendering();
        $this->registerMediaBladeComponents();
        $this->registerBlazeComponents();
        $this->registerPublishCommands();
        $this->registerFrontendRuntimeManifestContributors();

        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerAssets();
        $this->registerTailwindEventListeners();
        $this->registerVendorAssetConditions();
        $this->registerVendorCssJsAssets();
        $this->registerMediaUrlGenerator();
        $this->registerModelInterceptors();
        $this->registerSettingsSchemas();
        $this->registerPublicRuntimeData();
        $this->registerLayoutAreas();
        $this->registerLayoutContainerSchemaExtenders();
        $this->registerThemeChromeComponents();
        $this->registerThemeStudioDefinition();
    }

    public function packageRegistered(): void
    {
        $this->app->scoped(FoundationThemeAssetContributor::class);
        $this->app->singleton(
            OptionalExtensionAvailability::class,
            CapellOptionalExtensionAvailability::class,
        );

        $this->registerVendorNpmDependencies();
    }

    #[Override]
    protected function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerAssets(): void
    {
        $this->app->tag(FoundationThemeAssetContributor::class, FrontendAssetContributor::TAG);

        if (! $this->app->bound(AssetsRegistryInterface::class)) {
            return;
        }

        $registry = resolve(AssetsRegistryInterface::class);

        foreach (FoundationThemeAssetEnum::cases() as $asset) {
            $registry->registerAsset(
                $asset->getAsset(),
                new FrontendAssetData(component: $asset->getComponent()),
            );
        }
    }

    private function registerBladeDirectives(): void
    {
        BladeDirectives::register();
    }

    private function registerBlazeComponents(): void
    {
        if (config('capell-theme-foundation.blaze.enabled', false) !== true) {
            return;
        }

        if ($this->app->environment('testing')) {
            return;
        }

        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components');
    }

    private function registerTailwindEventListeners(): void
    {
        Event::listen(PackageInstalled::class, [RunTailwindAssetsOnPackageChange::class, 'handleInstalled']);
        Event::listen(PackageUninstalled::class, [RunTailwindAssetsOnPackageChange::class, 'handleUninstalled']);
    }

    private function registerMediaUrlGenerator(): void
    {
        config(['media-library.url_generator' => CapellUrlGenerator::class]);
    }

    private function registerFrontendRuntimeManifestContributors(): void
    {
        if (! interface_exists(FrontendRuntimeManifestContributor::class)) {
            return;
        }

        $this->app->tag([FoundationThemeRuntimeManifestContributor::class], FrontendRuntimeManifestContributor::TAG);
    }

    private function registerMediaBladeComponents(): void
    {
        Blade::component('capell::media.svg', Svg::class);
    }

    private function registerBladeComponents(): void
    {
        resolve(ViewFactory::class)->prependNamespace('capell', __DIR__ . '/../../resources/views');

        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');
        Blade::component(AppBodyComponent::class, 'capell::app.body');
        Blade::component(FooterIndexComponent::class, 'capell::footer.index');
        Blade::component(LayoutIndexComponent::class, 'capell::layout.index');
        Blade::component(LayoutMainComponent::class, 'capell::layout.main');
        Blade::component(NewsletterForm::class, 'capell::newsletter-form');
        Blade::component(ThemeFormEmbed::class, 'capell::form-embed');
    }

    private function registerSettingsSchemas(): void
    {
        $this->surface()->settingsClass('theme_foundation', FoundationThemeSettings::class);
        $this->surface()->settingsSchema('theme_foundation', FoundationThemeSettingsSchema::class);
        CapellAdmin::registerExtensionManagementSurface(ExtensionManagementSurfaceData::settings(
            packageName: self::$packageName,
            label: 'capell-theme-foundation::generic.theme_foundation',
            settingsGroup: 'theme_foundation',
            icon: 'heroicon-o-swatch',
        ));
    }

    private function registerPublicRuntimeData(): void
    {
        Event::listen(FrontendContextResolved::class, function (FrontendContextResolved $event): void {
            if ($event->context->getFrontendData('foundation.theme.tokens') === null) {
                $event->context->setFrontendData('foundation.theme.tokens', ResolveFoundationThemeTokensAction::run(
                    theme: $event->context->theme(),
                    site: $event->context->site(),
                ));
            }

            $this->preparePageRuntimeData($event);
            $this->prepareFooterRuntimeData($event);
        });

        Event::listen(FrontendRenderPreparing::class, function (FrontendRenderPreparing $event): void {
            $this->preparePageRenderData($event);
            $this->prepareFooterRenderData($event);
        });
    }

    private function preparePageRenderData(FrontendRenderPreparing $event): void
    {
        $site = $event->renderContext->site;
        $language = $event->renderContext->language;
        $page = $event->renderContext->page;

        if (! $site instanceof Site || ! $language instanceof Language || ! $page instanceof Page) {
            return;
        }

        if ($event->context->getFrontendData('foundation.page.ancestors') === null) {
            $event->context->setFrontendData(
                'foundation.page.ancestors',
                PageLoader::getPageAncestors($page, $language, $site),
            );
        }

        $frontendData = $event->context->getFrontendData();

        if (is_array($frontendData) && array_key_exists('foundation.page.home', $frontendData)) {
            return;
        }

        $event->context->setFrontendData(
            'foundation.page.home',
            PageLoader::getSiteHomePage($site, $language),
        );
    }

    private function prepareFooterRenderData(FrontendRenderPreparing $event): void
    {
        $site = $event->renderContext->site;
        $language = $event->renderContext->language;
        $page = $event->renderContext->page;
        $theme = $event->renderContext->theme;

        if (! $site instanceof Site || ! $language instanceof Language || ! $page instanceof Page || ! $theme instanceof Theme) {
            return;
        }

        $this->prepareFooterData(
            getFrontendData: fn (string $key): mixed => $event->context->getFrontendData($key),
            setFrontendData: fn (string $key, mixed $value) => $event->context->setFrontendData($key, $value),
            site: $site,
            language: $language,
            page: $page,
        );
    }

    private function preparePageRuntimeData(FrontendContextResolved $event): void
    {
        $site = $event->context->site();
        $language = $event->context->language();
        $page = $event->context->page();

        if (! $site instanceof Site || ! $language instanceof Language || ! $page instanceof Page) {
            return;
        }

        if ($event->context->getFrontendData('foundation.page.ancestors') === null) {
            $event->context->setFrontendData(
                'foundation.page.ancestors',
                PageLoader::getPageAncestors($page, $language, $site),
            );
        }

        $frontendData = $event->context->getFrontendData();

        if (is_array($frontendData) && array_key_exists('foundation.page.home', $frontendData)) {
            return;
        }

        $event->context->setFrontendData(
            'foundation.page.home',
            PageLoader::getSiteHomePage($site, $language),
        );
    }

    private function prepareFooterRuntimeData(FrontendContextResolved $event): void
    {
        $site = $event->context->site();
        $language = $event->context->language();
        $page = $event->context->page();
        $theme = $event->context->theme();

        if (! $site instanceof Site || ! $language instanceof Language || ! $page instanceof Page || ! $theme instanceof Theme) {
            return;
        }

        $this->prepareFooterData(
            getFrontendData: fn (string $key): mixed => $event->context->getFrontendData($key),
            setFrontendData: fn (string $key, mixed $value) => $event->context->setFrontendData($key, $value),
            site: $site,
            language: $language,
            page: $page,
        );
    }

    private function prepareFooterData(callable $getFrontendData, callable $setFrontendData, Site $site, Language $language, Page $page): void
    {
        if ($getFrontendData('foundation.footer.site_languages') !== null) {
            return;
        }

        $setFrontendData(
            'foundation.footer.contact_page',
            Page::getFirstPageByTypeForSite('contact', $site, $language),
        );
        $setFrontendData(
            'foundation.footer.site_languages',
            SiteLoader::pageLanguages($site, $language, $page),
        );
        $setFrontendData(
            'foundation.footer.latest_pages',
            PageLoader::getPages(
                language: $language,
                site: $site,
                limit: 4,
                ordering: PageOrderEnum::Latest,
                pageGroup: BlueprintGroupEnum::Default,
            ),
        );
        $setFrontendData(
            'foundation.footer.related_sites',
            SiteLoader::related($site, $language)
                ->map(function (Site $relatedSite): array {
                    $relations = $relatedSite->getRelations();
                    $siteDomain = $relations['siteDomain'] ?? null;
                    $translation = $relations['translation'] ?? null;

                    return [
                        'description' => data_get($translation, 'meta.description'),
                        'primaryColor' => $relatedSite->getThemeColor('primary'),
                        'title' => data_get($translation, 'title'),
                        'url' => data_get($siteDomain, 'full_url'),
                    ];
                })
                ->filter(fn (array $relatedSite): bool => is_string($relatedSite['url']) && $relatedSite['url'] !== '')
                ->values(),
        );
    }

    private function registerThemeChromeComponents(): void
    {
        $register = function (ThemeChromeRegistry $registry): void {
            $registry->registerHeader('capell::header.index', __('capell-admin::form.foundation_header'));
            $registry->registerFooter('capell::footer', __('capell-admin::form.foundation_footer'));
        };

        $this->app->afterResolving(ThemeChromeRegistry::class, $register);

        if ($this->app->resolved(ThemeChromeRegistry::class)) {
            $register($this->app->make(ThemeChromeRegistry::class));
        }
    }

    private function registerThemeStudioDefinition(): void
    {
        if (! interface_exists('Capell\\Core\\ThemeStudio\\Contracts\\SectionRenderer')) {
            return;
        }

        $register = function (ThemeRegistry $registry): void {
            $sectionRenderers = $this->themeStudioSectionRenderers();

            $registry->register(
                definition: self::definition(),
                themeRenderer: new ChromeSplitBladeThemeRenderer(
                    themeKey: self::THEME_KEY,
                    layoutView: 'capell-theme-foundation::theme.page',
                    sectionRenderers: $sectionRenderers,
                ),
                sectionRenderers: array_values($sectionRenderers),
            );
        };

        $this->app->afterResolving(ThemeRegistry::class, $register);

        if ($this->app->resolved(ThemeRegistry::class)) {
            $register($this->app->make(ThemeRegistry::class));
        }
    }

    /**
     * @return array<string, ViewSectionRenderer>
     */
    private function themeStudioSectionRenderers(): array
    {
        return [
            'navigation' => new ViewSectionRenderer(self::THEME_KEY, 'navigation', 'capell-theme-foundation::theme.sections.navigation', failLoudly: true),
            'hero' => new VariantViewSectionRenderer(
                themeKey: self::THEME_KEY,
                sectionKey: 'hero',
                baseView: 'capell-theme-foundation::theme.sections.hero',
                variantViews: [
                    'split' => 'capell-theme-foundation::theme.sections.hero--split',
                    'stacked' => 'capell-theme-foundation::theme.sections.hero--stacked',
                    'full-bleed' => 'capell-theme-foundation::theme.sections.hero--full-bleed',
                ],
                failLoudly: true,
            ),
            'features' => new ViewSectionRenderer(self::THEME_KEY, 'features', 'capell-theme-foundation::theme.sections.features', failLoudly: true),
            'proof' => new ViewSectionRenderer(self::THEME_KEY, 'proof', 'capell-theme-foundation::theme.sections.proof', failLoudly: true),
            'content-listing' => new VariantViewSectionRenderer(
                themeKey: self::THEME_KEY,
                sectionKey: 'content-listing',
                baseView: 'capell-theme-foundation::theme.sections.content-listing',
                variantViews: [
                    'grid' => 'capell-theme-foundation::theme.sections.content-listing--grid',
                    'rows' => 'capell-theme-foundation::theme.sections.content-listing--rows',
                    'masonry-safe' => 'capell-theme-foundation::theme.sections.content-listing--masonry-safe',
                ],
                failLoudly: true,
            ),
            'search' => new ViewSectionRenderer(self::THEME_KEY, 'search', 'capell-theme-foundation::theme.sections.search', failLoudly: true),
            'pagination' => new ViewSectionRenderer(self::THEME_KEY, 'pagination', 'capell-theme-foundation::theme.sections.pagination', failLoudly: true),
            'form' => new VariantViewSectionRenderer(
                themeKey: self::THEME_KEY,
                sectionKey: 'form',
                baseView: 'capell-theme-foundation::theme.sections.form',
                variantViews: [
                    'encouraging' => 'capell-theme-foundation::theme.sections.form--encouraging',
                ],
                failLoudly: true,
            ),
            'contact-split' => new ViewSectionRenderer(self::THEME_KEY, 'contact-split', 'capell-theme-foundation::theme.sections.contact-split', failLoudly: true),
            'cta' => new VariantViewSectionRenderer(
                themeKey: self::THEME_KEY,
                sectionKey: 'cta',
                baseView: 'capell-theme-foundation::theme.sections.cta',
                variantViews: [
                    'band' => 'capell-theme-foundation::theme.sections.cta--band',
                    'card' => 'capell-theme-foundation::theme.sections.cta--card',
                    'inline' => 'capell-theme-foundation::theme.sections.cta--inline',
                ],
                failLoudly: true,
            ),
            'footer' => new ViewSectionRenderer(self::THEME_KEY, 'footer', 'capell-theme-foundation::theme.sections.footer', failLoudly: true),
            'pricing-value-spectrum' => new VariantViewSectionRenderer(
                themeKey: self::THEME_KEY,
                sectionKey: 'pricing-value-spectrum',
                baseView: 'capell-theme-foundation::theme.sections.pricing-value-spectrum',
                variantViews: [
                    'compact' => 'capell-theme-foundation::theme.sections.pricing-value-spectrum--compact',
                ],
                failLoudly: true,
            ),
            'faq-search-discovery' => new VariantViewSectionRenderer(
                themeKey: self::THEME_KEY,
                sectionKey: 'faq-search-discovery',
                baseView: 'capell-theme-foundation::theme.sections.faq-search-discovery',
                variantViews: [
                    'categorised' => 'capell-theme-foundation::theme.sections.faq-search-discovery--categorised',
                ],
                failLoudly: true,
            ),
            'changelog-stream' => new VariantViewSectionRenderer(
                themeKey: self::THEME_KEY,
                sectionKey: 'changelog-stream',
                baseView: 'capell-theme-foundation::theme.sections.changelog-stream',
                variantViews: [
                    'grid' => 'capell-theme-foundation::theme.sections.changelog-stream--grid',
                ],
                failLoudly: true,
            ),
            'stats-display-band' => new VariantViewSectionRenderer(
                themeKey: self::THEME_KEY,
                sectionKey: 'stats-display-band',
                baseView: 'capell-theme-foundation::theme.sections.stats-display-band',
                variantViews: [
                    'light' => 'capell-theme-foundation::theme.sections.stats-display-band--light',
                ],
                failLoudly: true,
            ),
        ];
    }

    private function registerLayoutAreas(): void
    {
        $register = function (LayoutAreaRegistry $registry): void {
            $registry->register('header', __('capell-layout-builder::generic.header_area'));
            $registry->register('footer', __('capell-layout-builder::generic.footer_area'));
        };

        $this->app->afterResolving(LayoutAreaRegistry::class, $register);

        if ($this->app->resolved(LayoutAreaRegistry::class)) {
            $register($this->app->make(LayoutAreaRegistry::class));
        }
    }

    private function registerLayoutContainerSchemaExtenders(): void
    {
        if (! interface_exists(LayoutContainerSchemaExtender::class)) {
            return;
        }

        $alreadyTagged = collect($this->app->tagged(LayoutContainerSchemaExtender::TAG))
            ->contains(fn (object $extender): bool => $extender instanceof FoundationLayoutContainerSchemaExtender);

        if ($alreadyTagged) {
            return;
        }

        $this->app->singleton(FoundationLayoutContainerSchemaExtender::class);
        $this->app->tag(FoundationLayoutContainerSchemaExtender::class, LayoutContainerSchemaExtender::TAG);
    }

    private function registerModelInterceptors(): void
    {
        CapellCore::registerModelInterceptor(Theme::class, interceptorClass: FoundationThemeInterceptor::class);
    }

    private function registerVendorNpmDependencies(): void
    {
        $npmDependencies = config('capell-theme-foundation.npm_dependencies', []);

        if (! is_array($npmDependencies)) {
            return;
        }

        foreach ($npmDependencies as $package => $version) {
            if (! is_string($package)) {
                continue;
            }

            if ($package === '') {
                continue;
            }

            if (! is_string($version)) {
                continue;
            }

            if ($version === '') {
                continue;
            }

            CapellCore::registerVendorAsset(
                VendorAssetData::npmDependency($package, $version, self::$packageName),
            );
        }
    }

    private function registerVendorCssJsAssets(): void
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::buildAsset(
                path: 'vendor/capell-theme-foundation',
                file: 'resources/js/capell-frontend.js',
                packageName: self::$packageName,
                condition: 'theme-foundation-runtime',
            ),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('resources/css/theme-foundation.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('resources/css/widgets/foundation-widgets.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('tippy.js/dist/tippy.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/autoplay', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/pagination', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('swiper/css/navigation', self::$packageName),
        );
    }

    private function registerVendorAssetConditions(): void
    {
        resolve(VendorAssetConditionRegistry::class)->register(
            'theme-foundation-runtime',
            fn (FrontendAssetContextData $context): bool => $context->runtime->usesIslands
                || $context->runtime->usesLivewire
                || ($context->runtime->modules['theme-foundation-runtime'] ?? false),
        );
    }

    private function registerPublishCommands(): void
    {
        $this->publishes([
            __DIR__ . '/../../publishes/build' => public_path('vendor/capell-theme-foundation'),
        ], 'capell-theme-foundation-assets');

        $previewImages = [];
        $packageDirectories = glob(dirname(__DIR__, 3) . '/theme-*', GLOB_ONLYDIR) ?: [];

        foreach ($packageDirectories as $packageDirectory) {
            $packageName = basename($packageDirectory);

            if (! str_starts_with($packageName, 'theme-')) {
                continue;
            }

            $themeKey = substr($packageName, strlen('theme-'));
            $source = $packageDirectory . '/docs/screenshots/' . $themeKey . '-homepage.png';

            if (! is_file($source)) {
                continue;
            }

            $previewImages[$source] = public_path('vendor/capell/themes/' . $themeKey . '.png');
        }

        if ($previewImages !== []) {
            $this->publishes($previewImages, 'capell-theme-preview-images');
        }
    }

    private function registerLayoutBuilderRendering(): void
    {
        resolve(ViewFactory::class)->addNamespace(
            'capell-theme-foundation',
            __DIR__ . '/../../resources/views',
        );

        resolve(ViewFactory::class)->addNamespace(
            'capell',
            __DIR__ . '/../../resources/views',
        );

        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');
        Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell-theme-foundation');
        Blade::componentNamespace('Capell\\FoundationTheme\\View\\Components', 'capell');
        Blade::componentNamespace('Capell\\FoundationTheme\\View\\Components', 'capell-theme-foundation');
        Blade::component(PageBreadcrumbsComponent::class, 'capell::widget.page.breadcrumbs');
        Blade::component(ActionsComponent::class, 'capell::actions');
        Blade::component(ActionsComponent::class, 'capell-theme-foundation::actions');
        Blade::component(PageContentComponent::class, 'capell-widget-page-content');
        Blade::component(PageContentComponent::class, 'capell::widget.page.content');
        Blade::component(SlotComponent::class, 'capell::widget.slot');
        Blade::component('capell-theme-foundation::components.widget.wrapper', 'capell-layout-builder::widget.wrapper');
        Blade::component(PageChildrenComponent::class, 'capell::widget.page.children');
        Blade::component(PageLatestComponent::class, 'capell::widget.page.latest');
        Blade::component(PageSiblingsComponent::class, 'capell::widget.page.siblings');

        $registerLivewireComponents = function (): void {
            Livewire::component('capell::widget.pages', Pages::class);
            Livewire::component('capell-theme-foundation::widget.pages', Pages::class);
            Livewire::component('capell-theme-foundation::assets.table.page-assets', PageAssets::class);

            if (! method_exists(Livewire::getFacadeRoot(), 'addNamespace')) {
                return;
            }

            Livewire::addNamespace(
                namespace: 'capell',
                classNamespace: 'Capell\\FoundationTheme\\Livewire',
                viewPath: __DIR__ . '/../../resources/views/livewire',
                classPath: __DIR__ . '/../Livewire',
                classViewPath: __DIR__ . '/../../resources/views/livewire',
            );

            Livewire::addNamespace(
                namespace: 'capell-theme-foundation',
                classNamespace: 'Capell\\FoundationTheme\\Livewire',
                viewPath: __DIR__ . '/../../resources/views/livewire',
                classPath: __DIR__ . '/../Livewire',
                classViewPath: __DIR__ . '/../../resources/views/livewire',
            );

        };

        if ($this->app->isBooted()) {
            $registerLivewireComponents();
        } else {
            $this->app->booted($registerLivewireComponents);
        }

        $this->callAfterResolving(FrontendComponentRegistryInterface::class, function (FrontendComponentRegistryInterface $registry): void {
            $registry
                ->register(
                    key: FrontendComponentKeyEnum::SectionWidget->value,
                    component: 'capell::section.widget',
                    props: [
                        'asset',
                        'class',
                        'color',
                        'icon',
                        'image',
                        'linkText',
                        'loop',
                        'meta',
                        'size',
                        'summary',
                        'tags',
                        'title',
                        'url',
                    ],
                )
                ->register(
                    key: FrontendComponentKeyEnum::SectionTeamMember->value,
                    component: 'capell::section.team-member',
                    props: [
                        'asset',
                        'class',
                        'color',
                        'icon',
                        'image',
                        'linkText',
                        'loop',
                        'meta',
                        'size',
                        'summary',
                        'title',
                        'url',
                    ],
                );
        });
    }
}
