<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveLoadedLayoutContainerBackgroundImageAction
{
    use AsFake;
    use AsObject;

    public function handle(Layout $layout, string $containerKey): ?Media
    {
        if (! $layout->relationLoaded('media')) {
            return null;
        }

        $media = $layout->getRelation('media');

        if (! $media instanceof Collection) {
            return null;
        }

        $match = $media->first(
            static fn (mixed $media): bool => $media instanceof Media
                && $media->collection_name === $containerKey . '-background',
        );

        return $match instanceof Media ? $match : null;
    }
}
