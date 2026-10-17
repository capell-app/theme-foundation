<?php

declare(strict_types=1);

use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Enums\RenderableTypeEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\Frontend\Contracts\AssetsRegistryInterface;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\FrontendAssetData;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Blaze\Blaze;

/** @param array<string, mixed> $meta */
function foundationWidgetViewFixture(array $meta = [], int $count = 2): Widget
{
    $theme = new Theme(['meta' => ['secondary_containers' => []]]);
    app()->instance(FrontendContextReader::class, new FrontendContext(
        site: null,
        language: null,
        page: new Page,
        layout: null,
        theme: $theme,
        params: [],
        slug: null,
    ));
    Frontend::clearResolvedInstance(FrontendContextReader::class);
    Blade::anonymousComponentPath(dirname(__DIR__) . '/Fixtures/views', 'behaviour-fixtures');
    resolve(AssetsRegistryInterface::class)->registerAsset(AssetEnum::Page, new FrontendAssetData('fixture.asset'));
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(
        key: 'fixture.asset',
        type: RenderableTypeEnum::Asset,
        blade: 'behaviour-fixtures::asset',
    ));
    $widget = new Widget(['key' => 'prepared-assets', 'meta' => $meta]);
    $widget->setRelation('translation', null);
    $widget->setRelation('blueprint', null);
    $widget->setRelation('media', new Collection);
    $widget->setRelation('image', null);
    $assets = [];

    for ($index = 0; $index < $count; $index++) {
        $page = new Page(['name' => 'Prepared asset ' . ($index + 1)]);
        $page->setRelation('translation', null);
        $page->setRelation('blueprint', null);
        $page->setRelation('media', new Collection);
        $page->setRelation('pageUrl', null);
        $asset = new WidgetAsset(['asset_type' => $page->getMorphClass(), 'meta' => []]);
        $asset->setRelation('asset', $page);
        $asset->setRelation('media', new Collection);
        $assets[] = $asset;
    }

    $widget->setRelation('assets', new Collection($assets));

    return $widget;
}

/** @param array<string, mixed> $data */
function renderFoundationWidgetFixture(string $view, Widget $widget, array $data = []): string
{
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        /** @var view-string $viewName */
        $viewName = implode('', ['capell-theme-foundation::components.widget.asset.', $view]);

        return view($viewName, [
            'assets' => $widget->getRelation('assets'),
            'widget' => $widget, 'widgetIndex' => 0,
            'container' => [], 'containerKey' => 'main', 'containerIndex' => 0, 'containerWidth' => null,
            'loop' => (object) ['index' => 0, 'first' => true, 'last' => true],
            'attributes' => new ComponentAttributeBag,
            ...$data,
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
}

/** @return Collection<int, WidgetAsset> */
function foundationWidgetAssets(Widget $widget): Collection
{
    $assets = $widget->getRelation('assets');

    return $assets instanceof Collection ? $assets : new Collection;
}
