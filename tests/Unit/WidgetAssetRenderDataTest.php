<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\MediaFactory;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\FoundationTheme\Actions\BuildHeroRailItemsRenderDataAction;
use Capell\FoundationTheme\Actions\BuildPageContentRenderDataAction;
use Capell\FoundationTheme\Actions\BuildWidgetAssetRenderDataAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

it('builds widget asset render data from loaded relations only', function (): void {
    $media = MediaFactory::new()->make([
        'collection_name' => MediaCollectionEnum::Image->value,
    ]);
    $translation = new Translation;
    $translation->setRawAttributes([
        'title' => 'North Star',
        'content' => '<p>Guidance copy.</p>',
        'label' => 'North Star label',
    ]);
    $linkedPage = new Page;
    $asset = new class extends Model
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        protected $guarded = [];

        public function getMeta(string $key, mixed $default = null): mixed
        {
            return data_get($this->getAttribute('meta'), $key, $default);
        }
    };

    $asset->setRawAttributes([
        'meta' => [
            'icon' => 'heroicon-o-star',
            'position' => 'right',
            'social' => ['website' => 'https://example.test'],
            'tags' => ['Featured'],
        ],
    ]);
    $asset->setRelation('media', new Collection([$media]));
    $asset->setRelation('translation', $translation);
    $asset->setRelation('linkedPage', $linkedPage);

    $widgetAsset = new WidgetAsset(['asset_type' => Page::class]);
    $widgetAsset->setRelation('asset', $asset);

    DB::enableQueryLog();

    $renderData = BuildWidgetAssetRenderDataAction::run($widgetAsset);

    expect($renderData->image?->media?->is($media))->toBeTrue()
        ->and($renderData->linkedPage)->toBe($linkedPage)
        ->and($renderData->title)->toBe('North Star')
        ->and($renderData->alt)->toBe('North Star')
        ->and($renderData->content)->toBe('<p>Guidance copy.</p>')
        ->and($renderData->icon)->toBe('heroicon-o-star')
        ->and($renderData->position)->toBe('right')
        ->and($renderData->social)->toBe(['website' => 'https://example.test'])
        ->and($renderData->tags)->toBe(['Featured'])
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});

it('builds page content render data from loaded relations only', function (): void {
    $page = Page::factory()->make();
    $translation = new Translation;
    $translation->setRawAttributes([
        'title' => 'Loaded page title',
        'content' => '<p>Loaded page content.</p>',
    ]);

    $page->setRelation('translation', $translation);

    DB::enableQueryLog();

    $renderData = BuildPageContentRenderDataAction::run($page, ['content'], true);

    expect($renderData->title)->toBe('Loaded page title')
        ->and($renderData->content)->toBe('<p>Loaded page content.</p>')
        ->and($renderData->contentStructure)->toBe(ContentStructure::Html)
        ->and($renderData->hasContent)->toBeTrue()
        ->and($renderData->hasTitle)->toBeFalse()
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});

it('builds hero rail items from loaded explicit hero assets only', function (): void {
    $widget = new Widget;
    $widgetAsset = heroRailWidgetAsset('widget-card', 'Widget card');
    $page = new Page;
    $pageHeroAsset = heroRailWidgetAsset('hero-card', 'Hero card');
    $pageGenericAsset = heroRailWidgetAsset('card', 'Generic card');

    $widget->setRelation('assets', new Collection([$widgetAsset]));
    $page->setRelation('assets', new Collection([$pageHeroAsset, $pageGenericAsset]));

    DB::enableQueryLog();

    $pageItems = BuildHeroRailItemsRenderDataAction::run($widget, $page, 'page');
    $mixedItems = BuildHeroRailItemsRenderDataAction::run($widget, $page, 'mixed');
    $widgetItems = BuildHeroRailItemsRenderDataAction::run($widget, $page, 'widget');

    expect($pageItems)->toHaveCount(1)
        ->and($pageItems[0]->caption)->toBe('Hero card')
        ->and($mixedItems)->toHaveCount(2)
        ->and($mixedItems[0]->caption)->toBe('Hero card')
        ->and($mixedItems[1]->caption)->toBe('Widget card')
        ->and($widgetItems)->toHaveCount(1)
        ->and($widgetItems[0]->caption)->toBe('Widget card')
        ->and(DB::getQueryLog())->toBe([]);

    DB::disableQueryLog();
});

function heroRailWidgetAsset(string $role, string $caption): WidgetAsset
{
    $asset = new class extends Model
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        protected $guarded = [];

        public function getMeta(string $key, mixed $default = null): mixed
        {
            return data_get($this->getAttribute('meta'), $key, $default);
        }
    };
    $asset->setRawAttributes([
        'meta' => [
            'caption' => $caption,
            'role' => $role,
        ],
    ]);

    $widgetAsset = new WidgetAsset(['asset_type' => Page::class]);
    $widgetAsset->setRelation('asset', $asset);

    return $widgetAsset;
}
