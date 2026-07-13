<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Actions\ResolveImageSourceDataAction;
use Capell\Core\Contracts\Media\MediaContract;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Data\ImageSourceData;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Data\WidgetAssetRenderData;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildWidgetAssetRenderDataAction
{
    use AsObject;

    public function handle(WidgetAsset $widgetAsset): WidgetAssetRenderData
    {
        $asset = $this->loadedRelation($widgetAsset, 'asset');
        $translation = $asset instanceof Model ? $this->loadedRelation($asset, 'translation') : null;
        $blueprint = $asset instanceof Model ? $this->loadedRelation($asset, 'blueprint') : null;
        $meta = is_array(data_get($asset, 'meta')) ? data_get($asset, 'meta') : [];
        $title = $this->stringValue($translation, 'title');
        $placementTitle = $this->metaString($widgetAsset, 'title') ?? $this->metaString($widgetAsset, 'caption');
        $placementContent = $this->metaString($widgetAsset, 'content');
        $contentStructure = data_get($blueprint, 'content_structure');

        return new WidgetAssetRenderData(
            asset: $asset,
            image: $this->image($widgetAsset, $asset),
            linkedPage: $this->linkedPage($widgetAsset, $asset),
            translation: $translation,
            meta: $meta,
            alt: $this->stringValue($translation, 'label') ?? $this->stringValue($translation, 'title') ?? '',
            actions: $this->metaArray($asset, 'actions'),
            accent: $this->metaString($asset, 'accent'),
            caption: $placementTitle ?? $this->metaString($asset, 'caption') ?? $title,
            content: $placementContent ?? $this->stringValue($translation, 'content'),
            contentStructure: $placementContent !== null ? ContentStructure::Html : ($contentStructure instanceof ContentStructure ? $contentStructure : null),
            cropPreset: $this->metaString($asset, 'crop_preset'),
            headingSize: $this->metaString($asset, 'heading_size') ?? 'h3',
            headingWeight: $this->metaString($asset, 'heading_weight') ?? 'medium',
            hasTranslations: CapellCore::getAsset($this->assetRegistryName($widgetAsset->asset_type))->hasTranslations,
            icon: $this->metaString($asset, 'icon'),
            linkText: $this->stringValue($translation, 'link_text'),
            linkUrl: $this->linkedPageUrl($widgetAsset, $asset),
            position: $this->metaString($asset, 'position'),
            role: $this->metaString($asset, 'role'),
            social: $this->metaArray($asset, 'social'),
            status: $this->metaString($asset, 'status'),
            tags: $this->metaArray($asset, 'tags'),
            textAlign: $this->metaString($asset, 'align') ?? $this->metaString($blueprint, 'align'),
            title: $placementTitle ?? $title,
        );
    }

    private function assetRegistryName(string $assetType): string
    {
        if (! class_exists($assetType) || ! is_subclass_of($assetType, Model::class)) {
            return $assetType;
        }

        /** @var class-string<Model> $assetModelClass */
        $assetModelClass = $assetType;

        return (new $assetModelClass)->getMorphClass();
    }

    private function image(WidgetAsset $widgetAsset, mixed $asset): ?ImageSourceData
    {
        $media = $this->firstLoadedMedia($widgetAsset)
            ?? ($asset instanceof Model ? $this->firstLoadedMedia($asset) : null)
            ?? ($asset instanceof Model ? $this->loadedImage($asset) : null);

        $source = $this->metaValue($widgetAsset, 'image_source')
            ?? ($asset instanceof Model ? $this->metaValue($asset, 'image_source') : null)
            ?? $this->metaValue($widgetAsset, 'image')
            ?? ($asset instanceof Model ? $this->metaValue($asset, 'image') : null);

        return ResolveImageSourceDataAction::run($source, $media);
    }

    private function linkedPage(WidgetAsset $widgetAsset, mixed $asset): mixed
    {
        if ($asset instanceof Pageable) {
            return $asset;
        }

        if ($asset instanceof Model) {
            return $this->loadedRelation($asset, 'linkedPage');
        }

        return $this->loadedRelation($widgetAsset, 'linkedPage');
    }

    private function linkedPageUrl(WidgetAsset $widgetAsset, mixed $asset): ?string
    {
        $linkedPage = $this->linkedPage($widgetAsset, $asset);

        if (! $linkedPage instanceof Model) {
            return null;
        }

        $pageUrl = $this->loadedRelation($linkedPage, 'pageUrl');
        $fullUrl = data_get($pageUrl, 'full_url');

        return is_string($fullUrl) && $fullUrl !== '' ? $fullUrl : null;
    }

    private function loadedImage(Model $model): ?MediaContract
    {
        $image = $this->loadedRelation($model, 'image');

        return $image instanceof MediaContract ? $image : null;
    }

    private function firstLoadedMedia(Model $model): ?MediaContract
    {
        $media = $this->loadedRelation($model, 'media');

        if (! $media instanceof Collection) {
            return null;
        }

        $match = $media->first(
            static fn (mixed $media): bool => $media instanceof MediaContract
                && in_array((string) data_get($media, 'collection_name'), [
                    MediaCollectionEnum::Image->value,
                    MediaCollectionEnum::BackgroundImage->value,
                ], true),
        );

        if ($match instanceof MediaContract) {
            return $match;
        }

        $fallback = $media->first(static fn (mixed $media): bool => $media instanceof MediaContract);

        return $fallback instanceof MediaContract ? $fallback : null;
    }

    private function loadedRelation(Model $model, string $relation): mixed
    {
        if (! $model->relationLoaded($relation)) {
            return null;
        }

        return $model->getRelation($relation);
    }

    private function metaString(mixed $asset, string $key): ?string
    {
        if (! is_object($asset) || ! method_exists($asset, 'getMeta')) {
            return null;
        }

        $value = $asset->getMeta($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function metaArray(mixed $asset, string $key): array
    {
        if (! is_object($asset) || ! method_exists($asset, 'getMeta')) {
            return [];
        }

        $value = $asset->getMeta($key, []);

        return is_array($value) ? $value : [];
    }

    private function metaValue(mixed $asset, string $key): mixed
    {
        if (! is_object($asset) || ! method_exists($asset, 'getMeta')) {
            return null;
        }

        return $asset->getMeta($key);
    }

    private function stringValue(mixed $object, string $key): ?string
    {
        $value = data_get($object, $key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
