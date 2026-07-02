<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Media;
use Capell\FoundationTheme\Data\BannerImageRenderData;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildBannerImageRenderDataAction
{
    use AsObject;

    public function handle(Widget $widget, mixed $content, mixed $title, bool $rounded, mixed $reverseOrder): BannerImageRenderData
    {
        $backgroundImage = $this->firstLoadedWidgetMedia($widget, MediaCollectionEnum::BackgroundImage->value)
            ?? $this->firstLoadedWidgetMedia($widget, MediaCollectionEnum::Image->value)
            ?? $this->firstAssetMedia($widget);

        $meta = is_array($widget->meta) ? $widget->meta : [];
        $actions = $meta['actions'] ?? null;
        $hasContent = filled($content) || filled($title) || filled($actions);

        return new BannerImageRenderData(
            backgroundImage: $backgroundImage,
            actions: $actions,
            hasContent: $hasContent,
            imageRoundedClass: $this->imageRoundedClass($rounded, $hasContent, (bool) $reverseOrder),
        );
    }

    private function firstLoadedWidgetMedia(Widget $widget, string $collectionName): ?Media
    {
        if (! $widget->relationLoaded('media')) {
            return null;
        }

        $media = $widget->getRelation('media');

        if (! $media instanceof Collection) {
            return null;
        }

        $match = $media->first(
            fn (mixed $media): bool => $media instanceof Media && $media->collection_name === $collectionName,
        );

        return $match instanceof Media ? $match : null;
    }

    private function firstAssetMedia(Widget $widget): mixed
    {
        if (! $widget->relationLoaded('assets')) {
            return null;
        }

        $assets = $widget->getRelation('assets');

        if (! $assets instanceof Collection) {
            return null;
        }

        $firstAsset = $assets->first();

        if (! $firstAsset instanceof Model || ! $firstAsset->relationLoaded('media')) {
            return null;
        }

        $media = $firstAsset->getRelation('media');

        return $media instanceof Collection ? $media->first() : null;
    }

    private function imageRoundedClass(bool $rounded, bool $hasContent, bool $reverseOrder): string
    {
        if (! $rounded) {
            return '';
        }

        if (! $hasContent) {
            return ' rounded-lg';
        }

        return $reverseOrder ? ' rounded-r-lg' : ' rounded-l-lg';
    }
}
