<?php

declare(strict_types=1);

use Capell\Core\Data\PackageData;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Events\PackageInstalled;
use Capell\Core\Events\PackageUninstalled;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Tailwind\TailwindAssetsRegistry;
use Capell\FoundationTheme\Actions\BuildBannerImageRenderDataAction;
use Capell\FoundationTheme\Actions\BuildLayoutNeighborLinksDataAction;
use Capell\FoundationTheme\Actions\MarkPrimaryHeadingRenderedAction;
use Capell\FoundationTheme\Actions\ResolveLoadedLayoutContainerBackgroundImageAction;
use Capell\FoundationTheme\Actions\WidgetIsSlotAction;
use Capell\FoundationTheme\Console\Commands\GenerateTailwindAssetsCommand;
use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Capell\FoundationTheme\Listeners\RunTailwindAssetsOnPackageChange;
use Capell\FoundationTheme\Livewire\Assets\Table\PageAssets;
use Capell\FoundationTheme\Livewire\Widget\Pages as LivewirePages;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Settings\FoundationThemeSettingsMigrationProvider;
use Capell\FoundationTheme\Support\Blade\BladeDirectives;
use Capell\FoundationTheme\Support\Interceptors\Themes\FoundationThemeInterceptor;
use Capell\FoundationTheme\Support\Media\CapellUrlGenerator;
use Capell\FoundationTheme\Support\Tailwind\TailwindAssetsGenerator;
use Capell\FoundationTheme\View\Components\Actions as ActionsComponent;
use Capell\FoundationTheme\View\Components\Footer\Index as FooterIndex;
use Capell\FoundationTheme\View\Components\Footer\LatestPages;
use Capell\FoundationTheme\View\Components\Layout\Index;
use Capell\FoundationTheme\View\Components\Layout\Main;
use Capell\FoundationTheme\View\Components\Widget\Asset;
use Capell\FoundationTheme\View\Components\Widget\Asset\Accordion;
use Capell\FoundationTheme\View\Components\Widget\Asset\Carousel;
use Capell\FoundationTheme\View\Components\Widget\Navigation;
use Capell\FoundationTheme\View\Components\Widget\Page\Children;
use Capell\FoundationTheme\View\Components\Widget\Page\Content;
use Capell\FoundationTheme\View\Components\Widget\Page\Latest;
use Capell\FoundationTheme\View\Components\Widget\Page\Pages;
use Capell\FoundationTheme\View\Components\Widget\Page\Siblings;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

function foundationThemeCoverageView(mixed $view): View
{
    expect($view)->toBeInstanceOf(View::class);
    assert($view instanceof View);

    return $view;
}

it('generates tailwind assets from configured app sources and packages', function (): void {
    $targetPath = storage_path('framework/testing/capell-theme-foundation-coverage/frontend.css');

    config([
        'capell-theme-foundation.tailwind' => [
            'imports' => ['@tailwindcss/forms'],
            'plugins' => ['@tailwindcss/typography'],
            'sources' => ['resources/views/**/*.blade.php'],
            'validate_sources' => false,
        ],
    ]);

    $generated = (new TailwindAssetsGenerator(new Filesystem))->generate($targetPath);
    $css = (string) file_get_contents($targetPath);

    capell_expect($generated)->toBe([$targetPath])
        ->and($css)->toContain('@import "tailwindcss";')
        ->and($css)->toContain('@import "@tailwindcss/forms";')
        ->and($css)->toContain('@plugin "@tailwindcss/typography";')
        ->and($css)->toContain('@source "');
});

it('registers foundation theme provider runtime services and package boot hooks', function (): void {
    config([
        'capell-theme-foundation.blaze.enabled' => false,
        'capell-theme-foundation.npm_dependencies' => [
            '' => '^1.0',
            'invalid-version' => '',
            123 => '^1.0',
            'swiper' => '^11.0',
        ],
    ]);

    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);

    $provider = new FoundationThemeServiceProvider(app());
    $provider->packageRegistered();
    $provider->packageBooted();

    capell_expect(config('media-library.url_generator'))->toBe(CapellUrlGenerator::class);
});

it('collects default tailwind assets without writing files', function (): void {
    config([
        'capell-theme-foundation.tailwind.output_css' => 'resources/css/capell/frontend.css',
        'capell-theme-foundation.tailwind.imports' => ['swiper/css'],
        'capell-theme-foundation.tailwind.plugins' => ['@tailwindcss/forms'],
        'capell-theme-foundation.tailwind.sources' => ['resources/views/**/*.blade.php'],
    ]);

    $registry = (new TailwindAssetsGenerator(new Filesystem))->collect();

    capell_expect($registry)->toBeInstanceOf(TailwindAssetsRegistry::class)
        ->and($registry->imports())->toContain('swiper/css')
        ->and($registry->plugins())->toContain('@tailwindcss/forms')
        ->and($registry->sources())->not->toBeEmpty()
        ->and($registry->themeColors())->not->toBeEmpty();
});

it('generates tailwind assets from installed vendor assets and validates configured sources', function (): void {
    $targetDirectory = storage_path('framework/testing/capell-theme-foundation-vendor-assets');
    $targetPath = $targetDirectory . '/frontend.css';
    $matchedSource = $targetDirectory . '/views/example.blade.php';
    $packageName = 'vendor/theme-foundation-coverage';

    (new Filesystem)->ensureDirectoryExists(dirname($matchedSource));
    file_put_contents($matchedSource, '<div>Coverage</div>');

    config([
        'capell-theme-foundation.tailwind' => [
            'imports' => ['', 'resources/css/app.css', '@tailwindcss/forms'],
            'plugins' => ['@tailwindcss/typography'],
            'sources' => [
                '',
                123,
                $matchedSource,
                'resources/views/**/*.blade.php',
                'missing/**/*.blade.php',
            ],
            'validate_sources' => true,
        ],
    ]);

    CapellCore::forcePackageInstalled($packageName);
    CapellCore::registerVendorAsset(VendorAssetData::tailwindImport('tippy.js', $packageName));
    CapellCore::registerVendorAsset(VendorAssetData::tailwindImport('swiper/css', $packageName));
    CapellCore::registerVendorAsset(VendorAssetData::tailwindImport('resources/css/package.css', $packageName));
    CapellCore::registerVendorAsset(VendorAssetData::tailwindPlugin('@tailwindcss/container-queries', $packageName));
    CapellCore::registerVendorAsset(VendorAssetData::tailwindSource('resources/views/**/*.blade.php', $packageName));
    CapellCore::registerVendorAsset(VendorAssetData::tailwindThemeColor('coverage-accent', '#abcdef', $packageName));
    CapellCore::registerVendorAsset(VendorAssetData::tailwindThemeColor('bad;color', '#123456', $packageName));
    CapellCore::registerVendorAsset(VendorAssetData::tailwindImport('missing-package.css', 'vendor/not-installed'));

    (new TailwindAssetsGenerator(new Filesystem))->generate($targetPath);

    $css = (string) file_get_contents($targetPath);

    capell_expect($css)
        ->toContain('@import "tippy.js";')
        ->toContain('@import "swiper/css";')
        ->toContain('@import "@tailwindcss/forms";')
        ->toContain('package.css')
        ->not->toContain('vendor/' . $packageName . '/swiper/css')
        ->toContain('@plugin "@tailwindcss/container-queries";')
        ->toContain('@source "')
        ->toContain('--color-coverage-accent: #abcdef;')
        ->not->toContain('bad;color')
        ->not->toContain('missing-package.css');
});

it('runs foundation tailwind command report generate and package-change listener paths', function (): void {
    $targetPath = storage_path('framework/testing/capell-theme-foundation-command/frontend.css');

    $this->artisan(GenerateTailwindAssetsCommand::class, ['--report' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Tailwind assets report:');

    $this->artisan(GenerateTailwindAssetsCommand::class, ['--output-path' => $targetPath])
        ->assertSuccessful()
        ->expectsOutputToContain('Generated Tailwind assets at');

    $package = new PackageData(
        name: FoundationThemeServiceProvider::$packageName,
        type: PackageTypeEnum::Theme,
        serviceProviderClass: FoundationThemeServiceProvider::class,
        path: __DIR__,
    );
    $listener = new RunTailwindAssetsOnPackageChange;
    $listener->handleInstalled(new PackageInstalled($package));
    $listener->handleUninstalled(new PackageUninstalled($package));
});

it('builds banner image render data for empty and rounded widgets', function (): void {
    $widget = new Widget;
    $widget->meta = ['actions' => [['label' => 'Start']]];

    $data = BuildBannerImageRenderDataAction::run(
        widget: $widget,
        content: '',
        title: '',
        rounded: true,
        reverseOrder: true,
    );

    capell_expect($data->backgroundImage)->toBeNull()
        ->and($data->actions)->toBe([['label' => 'Start']])
        ->and($data->hasContent)->toBeTrue()
        ->and($data->imageRoundedClass)->toBe(' rounded-r-lg');
});

it('declares foundation settings schema and settings migrations', function (): void {
    $components = FoundationThemeSettingsSchema::make(Schema::make());
    $performanceComponents = foundationThemeCoverageChildComponents(foundationThemeCoverageChildComponents($components[0])[0]);
    $designTokenComponents = foundationThemeCoverageChildComponents(foundationThemeCoverageChildComponents($components[1])[0]);
    $darkDesignTokenComponents = foundationThemeCoverageChildComponents(foundationThemeCoverageChildComponents($components[2])[0]);
    $provider = new FoundationThemeSettingsMigrationProvider;

    capell_expect($components)->toHaveCount(3)
        ->and($components[0])->toBeInstanceOf(Section::class)
        ->and($components[1])->toBeInstanceOf(Section::class)
        ->and($components[2])->toBeInstanceOf(Section::class)
        ->and($performanceComponents)->toHaveCount(2)
        ->and($performanceComponents[0])->toBeInstanceOf(Checkbox::class)
        ->and($performanceComponents[1])->toBeInstanceOf(Checkbox::class)
        ->and($designTokenComponents)->toHaveCount(18)
        ->and($designTokenComponents[0])->toBeInstanceOf(ColorPicker::class)
        ->and($designTokenComponents[12])->toBeInstanceOf(ColorPicker::class)
        ->and($designTokenComponents[13])->toBeInstanceOf(Select::class)
        ->and($designTokenComponents[14])->toBeInstanceOf(Select::class)
        ->and($designTokenComponents[15])->toBeInstanceOf(Select::class)
        ->and($designTokenComponents[16])->toBeInstanceOf(Select::class)
        ->and($designTokenComponents[17])->toBeInstanceOf(Select::class)
        ->and($darkDesignTokenComponents)->toHaveCount(13)
        ->and($darkDesignTokenComponents[0])->toBeInstanceOf(ColorPicker::class)
        ->and($darkDesignTokenComponents[12])->toBeInstanceOf(ColorPicker::class)
        ->and($provider->getSettingMigrations())->toBe([
            '2026_05_10_190850_01_create_theme_foundation_settings',
            '2026_05_23_160819_add_theme_foundation_design_tokens',
            '2026_05_23_161002_refresh_theme_foundation_design_token_defaults',
            '2026_05_23_170001_add_theme_foundation_composition_tokens',
            '2026_05_23_171201_quiet_theme_foundation_composition_palette',
            '2026_05_23_180101_add_theme_foundation_image_tokens',
            '2026_06_07_000001_add_theme_foundation_dark_design_tokens',
            '2026_06_07_000002_add_theme_foundation_typography_tokens',
            '2026_07_05_000001_add_theme_foundation_motion_tokens',
        ])
        ->and($provider->migrations())->toBe([
            '2026_05_10_190850_01_create_theme_foundation_settings',
            '2026_05_23_160819_add_theme_foundation_design_tokens',
            '2026_05_23_161002_refresh_theme_foundation_design_token_defaults',
            '2026_05_23_170001_add_theme_foundation_composition_tokens',
            '2026_05_23_171201_quiet_theme_foundation_composition_palette',
            '2026_05_23_180101_add_theme_foundation_image_tokens',
            '2026_06_07_000001_add_theme_foundation_dark_design_tokens',
            '2026_06_07_000002_add_theme_foundation_typography_tokens',
            '2026_07_05_000001_add_theme_foundation_motion_tokens',
        ])
        ->and(FoundationThemeSettings::group())->toBe('theme_foundation')
        ->and(FoundationThemeSettings::schema())->toBe(FoundationThemeSettingsSchema::class)
        ->and(FoundationThemeSettings::sectionSpacingCssValueFor(null))->toBe(FoundationThemeSettings::SECTION_SPACING_OPTIONS['relaxed'])
        ->and(FoundationThemeSettings::widgetGapCssValueFor(null))->toBe(FoundationThemeSettings::WIDGET_GAP_OPTIONS['balanced'])
        ->and(FoundationThemeSettings::headingScaleCssValuesFor(null))->toBe(FoundationThemeSettings::HEADING_SCALE_OPTIONS['balanced']);

});

it('compiles foundation blade directives across build tools and buffer expressions', function (): void {
    BladeDirectives::register();

    config(['capell-theme-foundation.asset_build_tool' => 'asset']);

    $assetHtml = Blade::render('@buildAssets(["frontend.css", "frontend.js"], "vendor/capell")');
    $buffer = Blade::render(<<<'BLADE'
@capellBuffer($renderCard, string $title, array $attributes = ['data-value' => 'a,b'])
<span>{{ $title }} {{ $attributes['data-value'] }}</span>
@endcapellBuffer
{{ $renderCard('Hello') }}
BLADE);

    capell_expect($assetHtml)
        ->toContain('<link rel="stylesheet" href="http://localhost/vendor/capell/frontend.css">')
        ->toContain('<script src="http://localhost/vendor/capell/frontend.js"></script>')
        ->and($buffer)->toContain('<span>Hello a,b</span>');
});

it('throws for buffer directives without a target variable', function (): void {
    BladeDirectives::register();

    Blade::compileString('@capellBuffer()');
})->throws(InvalidArgumentException::class, 'The @capellBuffer directive requires a target variable.');

it('fills foundation theme defaults without overwriting existing theme meta', function (): void {
    $data = (new FoundationThemeInterceptor)->beforeCreate([
        'meta' => [
            'footer_spacing' => 'relaxed',
            'sticky_header' => false,
        ],
    ]);

    capell_expect($data['meta'])
        ->toHaveKey('assets', ['resources/css/capell/frontend.css'])
        ->toHaveKey('assets_path', 'build')
        ->toHaveKey('footer_spacing', 'relaxed')
        ->toHaveKey('sticky_header', false)
        ->toHaveKey('dark_mode_toggle', true);
});

it('covers small foundation layout helper actions', function (): void {
    $slotWidget = new Widget;
    $slotWidget->meta = ['type' => 'slot', 'name' => 'Sidebar'];

    $plainWidget = new Widget;
    $plainWidget->meta = [];

    MarkPrimaryHeadingRenderedAction::run();

    capell_expect(WidgetIsSlotAction::run($slotWidget))->toBeTrue()
        ->and(WidgetIsSlotAction::run($plainWidget))->toBeFalse();
});

it('skips empty asset widgets without touching frontend context', function (string $componentClass): void {
    config(['capell-layout-builder.widget.skip_render_empty' => true]);

    $widget = new Widget;
    $widget->setRelation('assets', collect());

    $component = new $componentClass(
        container: [],
        containerKey: 'main',
        widgetIndex: 0,
        loop: new stdClass,
        widget: $widget,
    );
    /** @var Asset|Carousel|Accordion $component */
    capell_expect($component->render())->toBe('');
})->with([
    'asset' => [Asset::class],
    'carousel' => [Carousel::class],
    'accordion' => [Accordion::class],
]);

it('reports latest footer pages when explicit pages are provided', function (): void {
    $emptyComponent = new LatestPages(headingClass: 'font-semibold', pages: collect());
    $filledComponent = new LatestPages(headingClass: 'font-semibold', pages: collect([new Page]));

    capell_expect($emptyComponent->hasPages())->toBeFalse()
        ->and($filledComponent->hasPages())->toBeTrue()
        ->and($filledComponent->render()->name())->toBe('capell::components.footer.latest-pages');
});

it('does not build neighbor links when page meta disables them', function (): void {
    $page = new Page;
    $page->meta = ['with_next_prev' => false];

    $neighbors = BuildLayoutNeighborLinksDataAction::run($page, new Site, new Language);

    capell_expect($neighbors->previousPage)->toBeNull()
        ->and($neighbors->nextPage)->toBeNull()
        ->and($neighbors->shouldRender())->toBeFalse();
});

it('resolves loaded layout container background images defensively', function (): void {
    $layout = new Layout;
    $media = new Media;
    $media->collection_name = 'main-background';

    capell_expect(ResolveLoadedLayoutContainerBackgroundImageAction::run($layout, 'main'))->toBeNull();

    $layout->setRelation('media', 'not-a-collection');

    capell_expect(ResolveLoadedLayoutContainerBackgroundImageAction::run($layout, 'main'))->toBeNull();

    $layout->setRelation('media', collect([$media]));

    capell_expect(ResolveLoadedLayoutContainerBackgroundImageAction::run($layout, 'main'))->toBe($media)
        ->and(ResolveLoadedLayoutContainerBackgroundImageAction::run($layout, 'sidebar'))->toBeNull();
});

it('rewrites media urls to the active frontend root or configured site base', function (): void {
    Storage::fake('public');
    config([
        'media-library.version_urls' => false,
        'capell-theme-foundation.use_site_domain_for_media' => true,
        'capell-theme-foundation.site_base_url' => 'https://cdn.example.test',
        'capell-theme-foundation.local_storage_url' => '',
    ]);

    $media = new Spatie\MediaLibrary\MediaCollections\Models\Media;
    $media->disk = 'public';
    $media->conversions_disk = 'public';
    $media->file_name = 'hero image.jpg';
    $media->setAttribute('updated_at', now());

    $pathGenerator = new class implements PathGenerator
    {
        public function getPath(Spatie\MediaLibrary\MediaCollections\Models\Media $media): string
        {
            return 'media/';
        }

        public function getPathForConversions(Spatie\MediaLibrary\MediaCollections\Models\Media $media): string
        {
            return 'media/conversions/';
        }

        public function getPathForResponsiveImages(Spatie\MediaLibrary\MediaCollections\Models\Media $media): string
        {
            return 'media/responsive';
        }
    };

    $generator = (new CapellUrlGenerator(resolve(Repository::class)))
        ->setMedia($media)
        ->setPathGenerator($pathGenerator);

    capell_expect($generator->getUrl())->toContain('https://cdn.example.test')
        ->and($generator->getResponsiveImagesDirectoryUrl())->toBe('https://cdn.example.test/storage/media/responsive/');

    $domain = new SiteDomain([
        'scheme' => 'https',
        'domain' => 'active.example.test',
    ]);
    resolve(FrontendState::class)->withDomain($domain);

    capell_expect($generator->getUrl())->toContain('https://active.example.test');
});

it('skips empty navigation and page listing widgets without public markup', function (string $componentClass): void {
    [$language, $site, $theme, $layout, $page] = foundationThemeCoverageFrontendContext();
    $hiddenType = new Blueprint;
    $hiddenType->meta = ['hidden' => true];

    $page->setRelation('type', $hiddenType);
    $page->setRelation('layout', $layout);

    resolve(FrontendState::class)
        ->withLanguage($language)
        ->withSite($site)
        ->withTheme($theme)
        ->withLayout($layout)
        ->withPage($page);

    $widget = Widget::factory()->create([
        'key' => 'coverage-widget',
        'meta' => [],
    ]);
    $widget->setRelation('assets', new EloquentCollection);

    $component = new $componentClass(
        container: [],
        containerKey: 'main',
        widgetIndex: 0,
        loop: new stdClass,
        widget: $widget,
    );
    /** @var Navigation|Children|Siblings|Latest|Pages $component */
    capell_expect($component->render())->toBe('');
})->with([
    'navigation' => [Navigation::class],
    'children' => [Children::class],
    'siblings' => [Siblings::class],
    'latest' => [Latest::class],
    'pages' => [Pages::class],
]);

it('renders page content and layout components from frontend context', function (): void {
    [$language, $site, $theme, $layout, $page] = foundationThemeCoverageFrontendContext([
        'with_next_prev' => false,
        'final_cta' => ['label' => 'Start'],
    ]);

    resolve(FrontendState::class)
        ->withLanguage($language)
        ->withSite($site)
        ->withTheme($theme)
        ->withLayout($layout)
        ->withPage($page);

    $widget = Widget::factory()->create([
        'key' => 'content-widget',
        'meta' => [],
    ]);
    $widget->setRelation('assets', new EloquentCollection);

    $content = new Content(
        container: [],
        containerKey: 'main',
        widgetIndex: 0,
        loop: new stdClass,
        widget: $widget,
    );
    $index = new Index;
    $main = new Main(
        layout: $layout,
        page: $page,
        theme: ['container' => 'wide'],
    );

    capell_expect($content->previousPage)->toBeNull()
        ->and($content->nextPage)->toBeNull()
        ->and(foundationThemeCoverageView($content->render())->name())->toBe('capell-theme-foundation::components.widget.page.content')
        ->and(foundationThemeCoverageView($index->render())->name())->toBe('capell::components.layout.index')
        ->and($index->isSystemPageLayout)->toBeFalse()
        ->and(foundationThemeCoverageView($main->render())->name())->toBe('capell::components.layout.main')
        ->and($main->finalCta)->toBe(['label' => 'Start']);
});

it('resolves action component links and public action payloads', function (): void {
    [$language, $site, $theme, $layout, $page] = foundationThemeCoverageFrontendContext();

    resolve(FrontendState::class)
        ->withLanguage($language)
        ->withSite($site)
        ->withTheme($theme)
        ->withLayout($layout)
        ->withPage($page);

    Route::post('/public-action', static fn (): string => 'ok')->name('capell-public-actions.submit');

    $component = new ActionsComponent(actions: [
        ['type' => 'link', 'url' => 'https://example.test', 'label' => 'External'],
        [
            'type' => 'video_popup',
            'video_url' => 'https://capell.app/storage/videos/capell-laravel-foundation.mp4',
            'label' => 'Watch video',
        ],
        [
            'type' => 'public_action',
            'public_action_key' => 'request-access',
            'label' => 'Request access',
            'access_gate_area' => 'beta',
            'source_id' => 'hero',
        ],
        ['type' => 'link', 'url' => ''],
        'ignored',
    ]);

    capell_expect($component->resolvedActions)->not->toBeEmpty()
        ->and($component->resolvedActions[0]['kind'])->toBe('link')
        ->and($component->resolvedActions[1]['kind'])->toBe('video_popup')
        ->and($component->resolvedActions[1]['video_url'])->toBe('https://capell.app/storage/videos/capell-laravel-foundation.mp4')
        ->and($component->render()->name())->toBe('capell-theme-foundation::components.actions.index');
});

it('renders video popup actions as lightbox triggers', function (): void {
    $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/actions/index.blade.php');

    capell_expect($view)
        ->toContain('($action[\'kind\'] ?? null) === \'video_popup\'')
        ->toContain("'lightbox action-item max-w-full whitespace-normal'")
        ->toContain(':data-lightbox="$action[\'video_url\']"')
        ->toContain('data-type="video"')
        ->toContain(':data-title="$action[\'label\'] ?? \'\'"');
});

it('builds footer context and table asset record keys', function (): void {
    [$language, $site, $theme, $layout, $page] = foundationThemeCoverageFrontendContext();
    $site->setRelation('siteDomain', null);

    resolve(FrontendState::class)
        ->withLanguage($language)
        ->withSite($site)
        ->withTheme($theme)
        ->withLayout($layout)
        ->withPage($page);

    $footer = new FooterIndex;
    $table = new PageAssets;

    capell_expect($footer->render()->name())->toBe('capell::components.footer.index')
        ->and($footer->hasFooterMenu)->toBeFalse()
        ->and($table->getTableRecordKey(['id' => 123]))->toBe('123')
        ->and(PageAssets::getResource())->toBeString();
});

it('builds page asset table queries with scoped exclusions', function (): void {
    $table = new PageAssets;
    $table->tableArguments = ['pageId' => 10];
    $table->existingRecords = [20, 30];

    $method = new ReflectionMethod(PageAssets::class, 'getTableQuery');
    $query = $method->invoke($table);

    capell_expect($query->toSql())->toContain('not')
        ->and($query->getEagerLoads())->toHaveKeys([
            'translations.language',
            'ancestors.type',
            'creator',
            'layout',
            'image',
            'media',
            'editor',
            'site.siteDomains',
            'type',
        ]);
});

it('hydrates livewire page widgets from opaque references and skips empty selections', function (): void {
    [$language, $site, $theme, $layout, $page] = foundationThemeCoverageFrontendContext();

    $widget = Widget::factory()->create([
        'key' => 'livewire-pages',
        'meta' => [
            'pagination' => true,
            'limit' => 3,
        ],
    ]);
    $layout->containers = [
        'main' => [
            'widgets' => [
                [
                    'widget_key' => $widget->key,
                    'occurrence' => 1,
                ],
            ],
        ],
    ];
    $layout->save();

    resolve(FrontendState::class)
        ->withLanguage($language)
        ->withSite($site)
        ->withTheme($theme)
        ->withLayout($layout)
        ->withPage($page);

    $component = new LivewirePages;
    $component->mount(OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'occurrence' => 1,
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'site_id' => $site->getKey(),
        'widget_index' => 0,
    ]));

    capell_expect($component->render())->toBe('<div style="display: none"></div>')
        ->and(LivewirePages::getViewName())->toBe('capell-theme-foundation::components.widget.asset.pages')
        ->and(LivewirePages::getWidgetByKey($widget->key)?->is($widget))->toBeTrue();
});

/**
 * @return array<int, object>
 */
function foundationThemeCoverageChildComponents(mixed $component): array
{
    throw_unless(is_object($component), RuntimeException::class, 'Expected foundation theme settings component.');

    $reflectionProperty = new ReflectionProperty($component, 'childComponents');
    $childComponents = $reflectionProperty->getValue($component);
    throw_unless(is_array($childComponents), RuntimeException::class, 'Expected foundation theme child components array.');

    $defaultComponents = $childComponents['default'] ?? [];

    if (! is_array($defaultComponents)) {
        return [];
    }

    $components = [];

    foreach ($defaultComponents as $defaultComponent) {
        if (is_object($defaultComponent)) {
            $components[] = $defaultComponent;
        }
    }

    return $components;
}

/**
 * @param  array<string, mixed>  $pageMeta
 * @return array{0: Language, 1: Site, 2: Theme, 3: Layout, 4: Page}
 */
function foundationThemeCoverageFrontendContext(array $pageMeta = []): array
{
    $language = Language::factory()->english()->create();
    $theme = Theme::factory()->defaultMeta()->create();
    $site = Site::factory()
        ->language($language)
        ->theme($theme)
        ->withTranslations($language, ['title' => 'Foundation Site'])
        ->create();
    $layout = Layout::factory()->site($site)->create([
        'admin' => [],
    ]);
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->withTranslations($language, [
            'title' => 'Foundation Page',
            'content' => '<p>Foundation content.</p>',
        ])
        ->create([
            'meta' => $pageMeta,
        ]);

    $site->load('translation');
    $page->load('translation');

    return [$language, $site, $theme, $layout, $page];
}
