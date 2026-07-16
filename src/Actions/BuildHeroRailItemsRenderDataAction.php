<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\FoundationTheme\Data\WidgetAssetRenderData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildHeroRailItemsRenderDataAction
{
    use AsFake;
    use AsObject;

    /**
     * @return Collection<int, WidgetAssetRenderData>
     */
    public function handle(Widget $widget, ?Pageable $page, string $source, int $limit = 4): Collection
    {
        $widgetAssets = $this->loadedAssets($widget);
        $pageAssets = in_array($source, ['page', 'mixed'], true)
            ? $this->loadedPageHeroAssets($page)
            : collect();

        $assets = match ($source) {
            'page' => $pageAssets,
            'mixed' => $pageAssets->merge($widgetAssets),
            default => $widgetAssets,
        };

        return $assets
            ->filter(static fn (mixed $asset): bool => $asset instanceof WidgetAsset)
            ->map(static fn (WidgetAsset $asset): WidgetAssetRenderData => BuildWidgetAssetRenderDataAction::run($asset))
            ->take(max(0, $limit))
            ->values();
    }

    /**
     * @return Collection<int, mixed>
     */
    private function loadedAssets(Model $model): Collection
    {
        if (! $model->relationLoaded('assets')) {
            return collect();
        }

        $assets = $model->getRelation('assets');

        return $assets instanceof Collection ? $assets : collect();
    }

    /**
     * @return Collection<int, mixed>
     */
    private function loadedPageHeroAssets(?Pageable $page): Collection
    {
        if (! $page instanceof Model) {
            return collect();
        }

        return $this->loadedAssets($page)
            ->filter(function (mixed $attachment): bool {
                if (! $attachment instanceof WidgetAsset) {
                    return false;
                }

                $renderData = BuildWidgetAssetRenderDataAction::run($attachment);
                $role = $renderData->role;

                return is_string($role) && str_starts_with($role, 'hero');
            });
    }
}
