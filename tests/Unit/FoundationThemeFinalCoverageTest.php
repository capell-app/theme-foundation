<?php

declare(strict_types=1);

use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Actions\BuildLayoutNeighborLinksDataAction;
use Capell\FoundationTheme\Actions\MarkPrimaryHeadingRenderedAction;
use Capell\FoundationTheme\Actions\ResolveLoadedWidgetBackgroundImageAction;
use Capell\FoundationTheme\Actions\WidgetIsSlotAction;
use Capell\FoundationTheme\Livewire\Widget\Pages as LivewirePages;
use Capell\FoundationTheme\Support\Blade\BladeDirectives;
use Capell\FoundationTheme\Support\Media\CapellUrlGenerator;
use Capell\FoundationTheme\Tests\Fixtures\FoundationThemeFinalPageAssetsHarness;
use Capell\FoundationTheme\Tests\Fixtures\FoundationThemeFinalPathGenerator;
use Capell\FoundationTheme\View\Components\Widget\Navigation;
use Capell\FoundationTheme\View\Components\Widget\Page\Breadcrumbs;
use Capell\FoundationTheme\View\Components\Widget\Page\Children;
use Capell\FoundationTheme\View\Components\Widget\Page\Content;
use Capell\FoundationTheme\View\Components\Widget\Page\Siblings;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation as NavigationModel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Mix;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

function foundationThemeFinalView(mixed $view): View
{
    expect($view)->toBeInstanceOf(View::class);
    assert($view instanceof View);

    return $view;
}

it('builds enabled layout neighbor links from adjacent published pages', function (): void {
    [$language, $site, $type] = foundationThemeFinalPageSurface();

    Page::factory()
        ->site($site)
        ->type($type)
        ->published(CarbonImmutable::parse('2026-04-01 10:00:00'))
        ->state(['order' => 1])
        ->withTranslations($language, ['title' => 'Previous'], slug: 'previous-page')
        ->create();

    $currentPage = Page::factory()
        ->site($site)
        ->type($type)
        ->published(CarbonImmutable::parse('2026-04-02 10:00:00'))
        ->state(['order' => 2])
        ->withTranslations($language, ['title' => 'Current'], slug: 'current-page')
        ->create(['meta' => ['with_next_prev' => true]]);

    Page::factory()
        ->site($site)
        ->type($type)
        ->published(CarbonImmutable::parse('2026-04-03 10:00:00'))
        ->state(['order' => 3])
        ->withTranslations($language, ['title' => 'Next'], slug: 'next-page')
        ->create();

    $neighbors = BuildLayoutNeighborLinksDataAction::run($currentPage, $site, $language);

    expect($neighbors->shouldRender())->toBeTrue()
        ->and($neighbors->previousPage)->toBeInstanceOf(Page::class)
        ->and($neighbors->nextPage)->toBeInstanceOf(Page::class);
});

it('mounts successful child and sibling page widgets with hydrated frontend context', function (): void {
    [$language, $site, $type] = foundationThemeFinalPageSurface();
    $theme = Theme::factory()->defaultMeta()->create();
    $layout = Layout::factory()->site($site)->create(['admin' => []]);

    $parentPage = Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->published(CarbonImmutable::parse('2026-04-01 10:00:00'))
        ->withTranslations($language, ['title' => 'Parent'], slug: 'parent-page')
        ->create();

    $currentChild = Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->parent($parentPage)
        ->published(CarbonImmutable::parse('2026-04-02 10:00:00'))
        ->withTranslations($language, ['title' => 'Current child'], slug: 'current-child')
        ->create();

    $siblingChild = Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->parent($parentPage)
        ->published(CarbonImmutable::parse('2026-04-03 10:00:00'))
        ->withTranslations($language, ['title' => 'Sibling child'], slug: 'sibling-child')
        ->create();

    $widget = Widget::factory()->create([
        'key' => 'page-list',
        'meta' => [
            'with_children_count' => true,
            'with_parent' => true,
            'with_date' => true,
        ],
    ]);

    foundationThemeFinalFrontendState($language, $site, $theme, $layout, $parentPage->load('type', 'layout'));

    $children = new Children([], 'main', 0, new stdClass, $widget);

    foundationThemeFinalFrontendState($language, $site, $theme, $layout, $currentChild->load('type', 'layout'));

    $siblings = new Siblings([], 'main', 0, new stdClass, $widget);
    $childrenPages = $children->pages ?? collect();
    $siblingPages = $siblings->pages ?? collect();

    expect($children->pages)->not->toBeNull()
        ->and($childrenPages->pluck('id')->all())->toContain($currentChild->id, $siblingChild->id)
        ->and(foundationThemeFinalView($children->render())->name())->toBe('capell-theme-foundation::components.widget.asset.pages')
        ->and($siblings->pages)->not->toBeNull()
        ->and($siblingPages->pluck('id')->all())->toContain($siblingChild->id)
        ->and($siblingPages->pluck('id')->all())->not->toContain($currentChild->id);
});

it('mounts the livewire pages widget around selected page assets', function (): void {
    [$language, $site, $type] = foundationThemeFinalPageSurface();
    $theme = Theme::factory()->defaultMeta()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'selected-pages'],
                ],
            ],
        ],
    ]);
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->published()
        ->withTranslations($language, ['title' => 'Current page'], slug: 'current-livewire-page')
        ->create();
    $selectedPage = Page::factory()
        ->site($site)
        ->type($type)
        ->published()
        ->withTranslations($language, ['title' => 'Selected page'], slug: 'selected-livewire-page')
        ->create();
    $widget = Widget::factory()->create([
        'key' => 'selected-pages',
        'meta' => [
            'limit' => 3,
            'order' => PageOrderEnum::Default->value,
            'pagination' => true,
            'with_image' => true,
        ],
    ]);

    WidgetAsset::factory()
        ->widget($widget)
        ->asset($selectedPage)
        ->create(['order' => 1]);

    foundationThemeFinalFrontendState($language, $site, $theme, $layout, $page->load('type', 'layout'));

    $component = new LivewirePages;
    $component->mount(OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => 'selected-pages',
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'site_id' => $site->getKey(),
        'widget_index' => 0,
        'occurrence' => 1,
    ]));

    $pagesProperty = new ReflectionProperty($component, 'pages');

    $componentPages = $pagesProperty->getValue($component);

    $html = $component->render();

    expect($html)->toContain('<div class="contents">')
        ->and($component->widget()->assets)->toHaveCount(1)
        ->and($componentPages->pluck('id')->all())->toContain($selectedPage->id);
});

it('covers page asset table query branches and selected record submission', function (): void {
    $language = Language::factory()->english()->create();
    $excludedPage = Page::factory()->withTranslations($language)->create();
    $uuidModel = new class extends Model
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        public function getKey(): mixed
        {
            return Uuid::fromString('8fd9d7f7-e9a3-44b5-a9d8-88e5fb308c92');
        }
    };
    $component = new FoundationThemeFinalPageAssetsHarness;
    $component->tableArguments = ['pageId' => $excludedPage->getKey()];
    $component->existingRecords = [$excludedPage->getKey()];
    $component->selectedTableRecords = [$excludedPage->getKey()];
    $component->actionModalId = 'asset-modal';

    $query = $component->exposeTableQuery();

    expect($query)->toBeInstanceOf(Builder::class)
        ->and($query->toSql())->toContain('not')
        ->and($component->getTableRecordKey($uuidModel))->toBe('8fd9d7f7-e9a3-44b5-a9d8-88e5fb308c92')
        ->and($component->exposePersistsTableFiltersInSession())->toBeTrue();

    $component->isDisabled = true;
    $component->selectRecords();
});

it('rewrites media urls for local overrides paths query strings and active domains', function (): void {
    Storage::fake('public');

    config([
        'media-library.version_urls' => true,
        'capell-theme-foundation.use_site_domain_for_media' => false,
        'capell-theme-foundation.local_storage_url' => 'https://static.example.test/files',
        'capell-theme-foundation.site_base_url' => '',
    ]);

    app()->instance(FrontendState::class, new FrontendState);

    $media = new Spatie\MediaLibrary\MediaCollections\Models\Media;
    $media->disk = 'public';
    $media->conversions_disk = 'public';
    $media->file_name = 'hero.jpg';
    $media->setAttribute('updated_at', CarbonImmutable::parse('2026-04-01 12:00:00'));

    $generator = (new CapellUrlGenerator(resolve(Repository::class)))
        ->setMedia($media)
        ->setPathGenerator(new FoundationThemeFinalPathGenerator);

    expect($generator->getPath())->toContain('media/hero.jpg')
        ->and($generator->getUrl())->toContain('/media/hero.jpg?v=');

    config(['capell-theme-foundation.use_site_domain_for_media' => true]);

    expect($generator->getUrl())->toStartWith('https://static.example.test/files');

    $domain = new SiteDomain([
        'scheme' => 'https',
        'domain' => 'active.example.test',
    ]);
    resolve(FrontendState::class)->withDomain($domain);

    expect($generator->getResponsiveImagesDirectoryUrl())->toBe('https://active.example.test/storage/media/responsive/');
});

it('compiles mix build assets and nested buffer argument parsing', function (): void {
    BladeDirectives::register();

    app()->instance(Mix::class, new class
    {
        public function __invoke(string $path, ?string $manifestDirectory = null): string
        {
            return rtrim((string) $manifestDirectory, '/') . '/' . ltrim($path, '/');
        }
    });

    $assets = Blade::render('@buildAssets(["app.css", "app.js"], "mix-build", "mix")');
    $buffer = Blade::render(<<<'BLADE'
@capellBuffer($renderComplex, string $title = "A, B", array $payload = ['nested' => ['x,y' => '{z}']])
<strong>{{ $title }} {{ $payload['nested']['x,y'] }}</strong>
@endcapellBuffer
{{ $renderComplex() }}
BLADE);

    expect($assets)
        ->toContain('<link rel="stylesheet" href="mix-build/app.css">')
        ->toContain('<script src="mix-build/app.js"></script>')
        ->and($buffer)->toContain('<strong>A, B {z}</strong>');
});

it('parses buffer expressions without arguments and ignores nested delimiter characters', function (): void {
    BladeDirectives::register();

    $buffer = Blade::render(<<<'BLADE'
@capellBuffer($renderEmpty)
<span>Empty args</span>
@endcapellBuffer
{{ $renderEmpty() }}
BLADE);

    $method = new ReflectionMethod(BladeDirectives::class, 'findFirstTopLevelComma');
    $position = $method->invoke(null, <<<'EXPRESSION'
$target['a,b']->call("x,\"y", ['nested' => "{value,still nested}"])
EXPRESSION);

    expect($buffer)->toContain('<span>Empty args</span>')
        ->and($position)->toBeNull();
});

it('renders navigation and breadcrumbs with frontend context data', function (): void {
    CapellCore::forcePackageInstalled('capell-app/navigation');

    [$language, $site, $type] = foundationThemeFinalPageSurface();
    $theme = Theme::factory()->defaultMeta()->create();
    $layout = Layout::factory()->site($site)->create(['admin' => []]);
    $parentPage = Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->published()
        ->withTranslations($language, ['title' => 'Parent'], slug: 'breadcrumb-parent')
        ->create();
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->parent($parentPage)
        ->published()
        ->withTranslations($language, ['title' => 'Current'], slug: 'breadcrumb-current')
        ->create();
    $navigation = NavigationModel::factory()
        ->site($site)
        ->language($language)
        ->items([
            [
                'label' => 'Docs',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => 'https://docs.example.test'],
            ],
        ])
        ->create(['key' => 'docs']);
    $navigationWidget = Widget::factory()->create([
        'key' => 'navigation-widget',
        'meta' => ['navigation_id' => $navigation->getKey()],
    ]);
    $breadcrumbsWidget = Widget::factory()->create(['key' => 'breadcrumbs-widget']);

    foundationThemeFinalFrontendState($language, $site, $theme, $layout, $page->load('type', 'layout', 'translation'));

    $navigationComponent = new Navigation([], 'main', 0, new stdClass, $navigationWidget);
    $navigationByKeyWidget = Widget::factory()->create([
        'key' => 'navigation-key-widget',
        'meta' => ['navigation' => 'docs'],
    ]);
    $navigationByKeyComponent = new Navigation([], 'main', 1, new stdClass, $navigationByKeyWidget);
    $emptyNavigation = NavigationModel::factory()
        ->site($site)
        ->language($language)
        ->items([])
        ->create(['key' => 'empty-docs']);
    $emptyNavigationWidget = Widget::factory()->create([
        'key' => 'navigation-empty-widget',
        'meta' => ['navigation_id' => $emptyNavigation->getKey()],
    ]);
    $emptyNavigationComponent = new Navigation([], 'main', 2, new stdClass, $emptyNavigationWidget);
    $breadcrumbs = new Breadcrumbs([], 'main', 1, new stdClass, $breadcrumbsWidget);

    expect($navigationComponent->items)->not->toBeNull()
        ->and($navigationComponent->items)->toHaveCount(1)
        ->and($navigationComponent->menu?->getKey())->toBe($navigation->getKey())
        ->and(foundationThemeFinalView($navigationComponent->render())->name())->toBe('capell-theme-foundation::components.widget.navigation.index')
        ->and($navigationByKeyComponent->menu?->getKey())->toBe($navigation->getKey())
        ->and($emptyNavigationComponent->render())->toBe('')
        ->and(foundationThemeFinalView($breadcrumbs->render())->name())->toBe('capell-theme-foundation::components.widget.page.breadcrumbs');
});

it('covers content neighbor links and small frontend context helper branches', function (): void {
    [$language, $site, $type] = foundationThemeFinalPageSurface();
    $theme = Theme::factory()->defaultMeta()->create();
    $layout = Layout::factory()->site($site)->create(['admin' => []]);

    Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->published(CarbonImmutable::parse('2026-05-01 10:00:00'))
        ->state(['order' => 1])
        ->withTranslations($language, ['title' => 'Previous'], slug: 'content-previous')
        ->create();
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->published(CarbonImmutable::parse('2026-05-02 10:00:00'))
        ->state(['order' => 2])
        ->withTranslations($language, ['title' => 'Current'], slug: 'content-current')
        ->create(['meta' => ['with_next_prev' => true]]);
    Page::factory()
        ->site($site)
        ->layout($layout)
        ->type($type)
        ->published(CarbonImmutable::parse('2026-05-03 10:00:00'))
        ->state(['order' => 3])
        ->withTranslations($language, ['title' => 'Next'], slug: 'content-next')
        ->create();
    $widget = Widget::factory()->create(['key' => 'content-neighbors']);
    $slotType = new class
    {
        public function getMeta(string $key): ?string
        {
            return $key === 'type' ? 'slot' : null;
        }
    };
    $slotWidget = new Widget;
    $slotWidget->setRelation('type', $slotType);

    $media = new Media;
    $media->collection_name = 'background_image';

    $backgroundWidget = new Widget;
    $backgroundWidget->setRelation('media', new Collection([$media]));

    foundationThemeFinalFrontendState($language, $site, $theme, $layout, $page->load('type', 'layout'));

    MarkPrimaryHeadingRenderedAction::run();

    $content = new Content([], 'main', 0, new stdClass, $widget);

    expect($content->previousPage)->toBeInstanceOf(Page::class)
        ->and($content->nextPage)->toBeInstanceOf(Page::class)
        ->and(WidgetIsSlotAction::run($slotWidget))->toBeTrue()
        ->and(ResolveLoadedWidgetBackgroundImageAction::run($backgroundWidget))->toBe($media)
        ->and(Frontend::getFrontendData('has_primary_heading'))->toBeTrue();
});

/**
 * @return array{0: Language, 1: Site, 2: Blueprint}
 */
function foundationThemeFinalPageSurface(): array
{
    $language = Language::factory()->english()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations($language, ['title' => 'Foundation Site'])
        ->create();
    $type = Blueprint::factory()->page()->create([
        'key' => 'foundation-page',
        'meta' => [
            'listable' => true,
            'with_next_prev' => true,
        ],
    ]);

    return [$language, $site, $type];
}

function foundationThemeFinalFrontendState(Language $language, Site $site, Theme $theme, Layout $layout, Page $page): void
{
    $state = new FrontendState;
    app()->instance(FrontendState::class, $state);
    app()->instance(FrontendContextReader::class, $state);
    app()->instance(CapellFrontendContext::class, new CapellFrontendContext($state));
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    resolve(FrontendState::class)
        ->withLanguage($language)
        ->withSite($site)
        ->withTheme($theme)
        ->withLayout($layout)
        ->withPage($page);
}
