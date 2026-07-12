<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Media;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveLoadedWidgetBackgroundImageAction
{
    use AsObject;

    public function handle(Widget $widget): ?Media
    {
        if (! $widget->relationLoaded('media')) {
            return null;
        }

        $media = $widget->getRelation('media');

        if (! $media instanceof Collection) {
            return null;
        }

        $match = $media->first(
            static fn (mixed $media): bool => $media instanceof Media
                && $media->collection_name === MediaCollectionEnum::BackgroundImage->value,
        );

        return $match instanceof Media ? $match : null;
    }
}
