<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\MediaFactory;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Actions\BuildAssetBannerItemsAction;
use Capell\FoundationTheme\Actions\BuildBannerImageRenderDataAction;
use Capell\FoundationTheme\Actions\ResolveLoadedWidgetBackgroundImageAction;
use Capell\FoundationTheme\Livewire\Widget\AbstractWidget as LivewireWidget;
use Capell\FoundationTheme\View\Components\Widget\Page\AbstractPagesWidget;
use Capell\FoundationTheme\View\Components\Widget\Page\Breadcrumbs as BreadcrumbsWidget;
use Capell\FoundationTheme\View\Components\Widget\Page\Content as ContentWidget;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Blaze\Blaze;

function sidebarPageWidgetStyleView(mixed $view): View
{
    expect($view)->toBeInstanceOf(View::class);
    assert($view instanceof View);

    return $view;
}

test('sidebar page widgets expose stable styling and current page hooks', function (): void {
    $component = new class(container: [], containerKey: 'sidebar', widgetIndex: 0, loop: (object) ['index' => 0], widget: new Widget(['key' => 'pages', 'name' => 'Pages', 'meta' => ['view_file' => 'capell::components.no-results']])) extends AbstractPagesWidget
    {
        protected function mountWidget(): void
        {
            $this->pages = new Collection([
                ['title' => 'Current page', 'url' => '/current'],
            ]);
        }
    };

    $view = sidebarPageWidgetStyleView($component->render());

    expect($view->getData())->toHaveKey('pages')
        ->and($view->getData()['pages'])->toHaveCount(1)
        ->and($view->getData()['containerKey'])->toBe('sidebar');
});

test('banner image render data uses only preloaded widget media', function (): void {
    $media = MediaFactory::new()->make([
        'collection_name' => MediaCollectionEnum::BackgroundImage->value,
    ]);
    $widget = new Widget(['key' => 'banner', 'meta' => []]);
    $widget->setRelation('media', new Collection([$media]));

    $renderData = BuildBannerImageRenderDataAction::run($widget, null, null, false, false);

    expect($renderData->backgroundImage)->toBe($media);
});

test('banner image render data does not lazy-load widget media', function (): void {
    $widget = Widget::factory()->create(['key' => 'banner', 'meta' => []]);

    DB::enableQueryLog();

    $renderData = BuildBannerImageRenderDataAction::run($widget, null, null, false, false);

    expect($renderData->backgroundImage)->toBeNull()
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});

test('widget wrapper background image resolution does not lazy-load media', function (): void {
    $widget = Widget::factory()->create(['key' => 'section', 'meta' => []]);

    DB::enableQueryLog();

    $backgroundImage = ResolveLoadedWidgetBackgroundImageAction::run($widget);

    expect($backgroundImage)->toBeNull()
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});

test('asset banner render data uses only loaded relations', function (): void {
    $media = MediaFactory::new()->make([
        'collection_name' => MediaCollectionEnum::Image->value,
    ]);
    $widgetAsset = WidgetAsset::factory()->make([
        'asset_type' => Widget::class,
    ]);
    $widgetAsset->setRelation('media', new Collection([$media]));

    $widget = new Widget(['key' => 'asset-banners', 'meta' => []]);
    $widget->setRelation('assets', new Collection([$widgetAsset]));

    $items = BuildAssetBannerItemsAction::run($widget);
    $item = $items->firstOrFail();

    expect($items)->toHaveCount(1)
        ->and($item->image)->toBe($media);
});

test('asset banner render data uses linked page loaded on the asset model', function (): void {
    $linkedPage = new Page;
    $linkedPage->setRelation('pageUrl', (object) ['full_url' => '/linked-page']);
    $linkedPage->setRelation('translation', (object) ['link_text' => 'Read more']);

    $asset = new Widget(['key' => 'linked-asset']);
    $asset->setRelation('linkedPage', $linkedPage);

    $widgetAsset = WidgetAsset::factory()->make([
        'asset_type' => Widget::class,
    ]);
    $widgetAsset->setRelation('asset', $asset);

    $widget = new Widget(['key' => 'asset-banners', 'meta' => []]);
    $widget->setRelation('assets', new Collection([$widgetAsset]));

    $items = BuildAssetBannerItemsAction::run($widget);
    $item = $items->firstOrFail();

    expect($items)->toHaveCount(1)
        ->and($item->url)->toBe('/linked-page')
        ->and($item->linkText)->toBe('Read more');
});

test('asset banner render data does not lazy-load relations', function (): void {
    $widget = Widget::factory()->create(['key' => 'asset-banners', 'meta' => []]);

    DB::enableQueryLog();

    $items = BuildAssetBannerItemsAction::run($widget);

    expect($items)->toHaveCount(0)
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});

test('public livewire widgets expose only an opaque reference as public state', function (): void {
    $reflection = new ReflectionClass(LivewireWidget::class);
    $publicProperties = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))
        ->map(fn (ReflectionProperty $property): string => $property->getName())
        ->all();

    expect($publicProperties)
        ->toContain('widgetReference')
        ->not->toContain('container')
        ->not->toContain('widget')
        ->not->toContain('widgetData')
        ->not->toContain('loop');
});

test('layout livewire widgets preserve extension widget data mount parameters', function (): void {
    $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/layout/widget.blade.php');

    expect($view)
        ->toContain('OpaqueWidgetReference::encode')
        ->toContain("'widgetReference' => \$widgetReference")
        ->toContain("'widget_data' => \$widgetData")
        ->not->toContain("'widgetData' => \$widgetData");
});

test('opaque widget references do not expose raw public context', function (): void {
    $reference = OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => 'private-livewire-widget',
        'page_id' => 123,
        'site_id' => 456,
    ]);

    expect($reference)
        ->not->toContain('container_key')
        ->not->toContain('private-livewire-widget')
        ->not->toContain('page_id')
        ->not->toContain('site_id')
        ->and(OpaqueWidgetReference::decode($reference))
        ->toMatchArray([
            'container_key' => 'main',
            'widget_key' => 'private-livewire-widget',
            'page_id' => 123,
            'site_id' => 456,
        ]);
});

test('public livewire widgets resolve the scoped layout widget clone', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $widget = Widget::factory()->create(['key' => 'featured-pages']);
    $firstOccurrenceAsset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'First occurrence']);
    $secondOccurrenceAsset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Second occurrence']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1],
                    ['widget_key' => $widget->key, 'occurrence' => 2, 'meta' => ['show_page_title' => true]],
                ],
            ],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    WidgetAsset::factory()
        ->widget($widget)
        ->asset($firstOccurrenceAsset)
        ->page($page, 'main', 1)
        ->create();

    WidgetAsset::factory()
        ->widget($widget)
        ->asset($secondOccurrenceAsset)
        ->page($page, 'main', 2)
        ->create();

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: $site,
        language: $language,
        page: $page,
        layout: $layout,
        theme: null,
        params: [],
        slug: null,
    )));

    $component = new class extends LivewireWidget
    {
        /** @var list<int> */
        public array $assetIds = [];

        protected function mountWidget(): void
        {
            $this->assetIds = $this->widget()->assets
                ->pluck('asset_id')
                ->map(fn (mixed $assetId): int => (int) $assetId)
                ->values()
                ->all();
        }
    };

    $component->mount(OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'occurrence' => 2,
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'site_id' => $site->getKey(),
        'widget_index' => 1,
    ]));

    expect($component->assetIds)->toBe([(int) $secondOccurrenceAsset->getKey()]);

    $renderData = sidebarPageWidgetStyleView($component->render())->getData();

    expect($renderData['container'])->toHaveKey('widgets')
        ->and($renderData['widgetData']['meta']['show_page_title'])->toBeTrue()
        ->and($renderData['widgetData']['occurrence'])->toBe(2);

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: null,
        language: null,
        page: null,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    )));
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    $hydratedComponent = new class extends LivewireWidget
    {
        protected function mountWidget(): void {}
    };

    $hydratedComponent->mount(OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'occurrence' => 2,
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'site_id' => $site->getKey(),
        'widget_data' => [
            'meta' => ['show_page_title' => true],
            'tracking_key' => 'kept-in-reference',
        ],
        'widget_index' => 1,
    ]));
    $hydratedData = sidebarPageWidgetStyleView($hydratedComponent->render())->getData();

    expect($hydratedData['widgetData']['tracking_key'])->toBe('kept-in-reference')
        ->and($hydratedData['widgetData']['meta']['show_page_title'])->toBeTrue();
});

test('public livewire widgets reject references without scoped page and site ids', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $widget = Widget::factory()->create(['key' => 'legacy-featured-pages']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    $component = new class extends LivewireWidget
    {
        protected function mountWidget(): void
        {
            $this->widget();
        }
    };

    expect(fn (): null => $component->mount(OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'occurrence' => 1,
        'widget_index' => 0,
    ])))
        ->toThrow(Exception::class, 'Widget reference is invalid');
});

test('public livewire widgets can hydrate widgets from global layouts', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $widget = Widget::factory()->create(['key' => 'global-featured-pages']);
    $layout = Layout::factory()->create([
        'site_id' => null,
        'containers' => [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => $widget->key,
                        'occurrence' => 1,
                        'meta' => [
                            'page_content' => ['title', 'content'],
                            'show_page_title' => true,
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: $site,
        language: $language,
        page: $page,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    )));
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    $component = new class extends LivewireWidget
    {
        public string $resolvedKey = '';

        protected function mountWidget(): void
        {
            $this->resolvedKey = $this->widget()->key;
        }
    };

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

    expect($component->resolvedKey)->toBe('global-featured-pages');
});

test('public livewire widgets reject global layout references replayed under another site', function (): void {
    $language = Language::factory()->create();
    $referenceSite = Site::factory()->create(['language_id' => $language->getKey()]);
    $currentSite = Site::factory()->create(['language_id' => $language->getKey()]);
    $widget = Widget::factory()->create(['key' => 'global-cross-site-pages']);
    $layout = Layout::factory()->create([
        'site_id' => null,
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);
    $referencePage = Page::factory()->site($referenceSite)->layout($layout)->withTranslations($language)->create();
    $currentPage = Page::factory()->site($currentSite)->layout($layout)->withTranslations($language)->create();

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: $currentSite,
        language: $language,
        page: $currentPage,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    )));
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    $component = new class extends LivewireWidget
    {
        protected function mountWidget(): void
        {
            $this->widget();
        }
    };

    expect(fn (): null => $component->mount(OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'occurrence' => 1,
        'page_id' => $referencePage->getKey(),
        'page_type' => $referencePage->getMorphClass(),
        'site_id' => $referenceSite->getKey(),
        'widget_index' => 0,
    ])))
        ->toThrow(Exception::class, 'Widget not found');
});

test('public livewire page content widgets render from encrypted context without ambient frontend state', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $widget = Widget::factory()->create([
        'key' => 'page-content',
        'meta' => ['view_file' => 'capell-theme-foundation::components.widget.page.content'],
    ]);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language, [
        'title' => 'Hydrated page title',
        'content' => '<p>Hydrated page content</p>',
    ])->create();

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: null,
        language: null,
        page: null,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    )));
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    $component = new class extends LivewireWidget
    {
        protected static string $defaultView = 'capell-theme-foundation::components.widget.page.content';

        protected function mountWidget(): void {}
    };

    $component->mount(OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'occurrence' => 1,
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'site_id' => $site->getKey(),
        'widget_data' => [
            'meta' => [
                'page_content' => ['title', 'content'],
                'show_page_title' => true,
            ],
        ],
        'widget_index' => 0,
    ]));

    $view = sidebarPageWidgetStyleView($component->render());
    $renderData = $view->getData();
    $renderedPage = $renderData['pageRecord'];
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        $html = $view->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect($renderedPage->translation->title)->toBe('Hydrated page title')
        ->and($renderedPage->translation->content)->toBe('<p>Hydrated page content</p>')
        ->and($renderData['widgetData']['meta']['page_content'])->toBe(['title', 'content'])
        ->and($html)->toContain('Hydrated page title')
        ->and($html)->toContain('Hydrated page content')
        ->and(file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/page/content.blade.php'))
        ->not->toContain('Frontend::page()');
});

test('asset page widget view does not query or lazy-load optional item parents', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $theme = Theme::factory()->make(['meta' => ['secondary_containers' => []]]);
    $currentPage = Page::factory()->site($site)->create();
    $parentPage = Page::factory()->site($site)->create();
    $item = Page::factory()->site($site)->parent($parentPage)->create(['name' => 'Child Page']);

    $currentPage->setRelation('translation', null);
    $currentPage->setRelation('blueprint', null);

    $item->setRelation('translation', (object) [
        'summary' => 'Loaded summary',
        'title' => 'Loaded child title',
    ]);
    $item->setRelation('pageUrl', (object) ['full_url' => '/child-page']);

    $widget = new Widget(['key' => 'featured-pages', 'name' => 'Featured Pages', 'meta' => []]);
    $widget->setRelation('translation', null);

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: $site,
        language: $language,
        page: $currentPage,
        layout: null,
        theme: $theme,
        params: [],
        slug: null,
    )));
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    $previous = EloquentModel::preventsLazyLoading();
    EloquentModel::preventLazyLoading();
    DB::enableQueryLog();

    try {
        view('capell-theme-foundation::components.widget.asset.pages', [
            'widget' => $widget,
            'widgetData' => ['meta' => []],
            'container' => [],
            'containerKey' => 'main',
            'containerWidth' => null,
            'index' => 0,
            'loop' => (object) ['index' => 0],
            'pages' => new Collection([$item]),
            'withParent' => true,
        ])->render();
    } finally {
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        EloquentModel::preventLazyLoading($previous);
    }

    expect(file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/asset/pages.blade.php'))
        ->not->toContain('loadParent(')
        ->and($queries)->toBe([]);
});

test('breadcrumbs render data does not lazy-load optional page and site relations', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $page = Page::factory()->site($site)->create();
    $widget = new Widget(['key' => 'breadcrumbs', 'name' => 'Breadcrumbs', 'meta' => ['view_file' => 'capell-theme-foundation::components.widget.page.breadcrumbs']]);

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: $site,
        language: $language,
        page: $page,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    )));
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    $previous = EloquentModel::preventsLazyLoading();
    EloquentModel::preventLazyLoading();

    try {
        $component = new BreadcrumbsWidget(
            container: [],
            containerKey: 'main',
            widgetIndex: 0,
            loop: (object) ['index' => 0],
            widget: $widget,
        );

        expect($component->render())->toBeInstanceOf(View::class);
    } finally {
        EloquentModel::preventLazyLoading($previous);
    }
});

test('content page widget ignores contextless hydration when resolving next previous links', function (): void {
    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: null,
        language: null,
        page: null,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    )));
    Frontend::clearResolvedInstance(CapellFrontendContext::class);

    $widget = new Widget(['key' => 'content', 'name' => 'Content', 'meta' => ['view_file' => 'capell::components.no-results']]);

    $component = new ContentWidget(
        container: [],
        containerKey: 'main',
        widgetIndex: 0,
        loop: (object) ['index' => 0],
        widget: $widget,
    );

    expect($component->previousPage)->toBeNull()
        ->and($component->nextPage)->toBeNull();
});

test('public livewire widgets reject references from another frontend site', function (): void {
    $language = Language::factory()->create();
    $currentSite = Site::factory()->create(['language_id' => $language->getKey()]);
    $otherSite = Site::factory()->create(['language_id' => $language->getKey()]);
    $widget = Widget::factory()->create(['key' => 'featured-pages']);
    $layout = Layout::factory()->site($otherSite)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);
    $page = Page::factory()->site($currentSite)->withTranslations($language)->create();

    app()->instance(CapellFrontendContext::class, new CapellFrontendContext(new FrontendContext(
        site: $currentSite,
        language: $language,
        page: $page,
        layout: null,
        theme: null,
        params: [],
        slug: null,
    )));

    $component = new class extends LivewireWidget
    {
        protected function mountWidget(): void
        {
            $this->widget();
        }
    };

    expect(fn (): null => $component->mount(OpaqueWidgetReference::encode([
        'container_key' => 'main',
        'widget_key' => $widget->key,
        'language_id' => $language->getKey(),
        'layout_id' => $layout->getKey(),
        'occurrence' => 1,
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'site_id' => $currentSite->getKey(),
        'widget_index' => 0,
    ])))
        ->toThrow(Exception::class, 'Widget not found');
});
