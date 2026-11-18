<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Enums\CacheEnum;
use Capell\Core\Events\FrontendSurrogateKeysInvalidated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Theme;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Core\Support\TableName;
use Capell\FoundationTheme\Support\Themes\RetiredThemeReferenceScanner;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Runs the cache and CDN side effects of a committed theme migration or
 * rollback: the writes themselves are made without model events so nothing is
 * purged and re-cached before the transaction commits.
 *
 * Page render data is keyed on the site's and layout's updated_at, which the
 * move bumps. The CDN is purged for every site that renders a page through a
 * moved site, a moved layout (global layouts included) or a layout or page
 * that places a remapped widget. Public widget snapshots built for those
 * layouts, or for the theme the records left, are revoked and rebuilt on the
 * next render.
 */
class RefreshMigratedThemeOutputAction
{
    use AsFake;
    use AsObject;

    private const int LIKE_CHUNK = 50;

    /**
     * @param  list<int>  $siteIds  sites that changed theme
     * @param  list<int>  $layoutIds  layouts that changed theme
     * @param  list<int>  $widgetIds  widgets that changed renderable
     */
    public function handle(Theme $theme, array $siteIds, ?int $previousThemeId = null, array $layoutIds = [], array $widgetIds = []): void
    {
        CapellCoreHelper::flushCache([
            CacheEnum::Site,
            CacheEnum::AllSites,
            CacheEnum::HasDefaultSite,
            CacheEnum::SiteLanguages,
            CacheEnum::LanguageByIdOrSite,
            CacheEnum::RelationExists,
            CacheEnum::HasFoundationTheme,
        ]);

        // Touching the committed record runs the theme observer: it purges the
        // CDN for every site now on this theme and refreshes its preview image.
        $theme->refresh()->touch();

        // The widgets were rewritten without model events; touching each runs
        // the frontend cache observers that a saved widget runs, so cached
        // layouts drop the renderable they named before the move.
        if ($widgetIds !== []) {
            Widget::withTrashed()->whereKey($widgetIds)->get()->each(static fn (Widget $widget): bool => $widget->touch());
        }

        $widgetLayoutIds = $this->layoutsPlacing($widgetIds);
        $affectedLayoutIds = array_values(array_unique([...$layoutIds, ...$widgetLayoutIds]));
        $affectedSiteIds = array_values(array_unique([
            ...$siteIds,
            ...$this->sitesRenderingThrough($affectedLayoutIds),
            ...$this->sitesWithPagesPlacing($widgetIds),
        ]));
        sort($affectedSiteIds);

        $this->revokeWidgetSnapshots($previousThemeId, $siteIds, $layoutIds, $widgetLayoutIds);

        if ($affectedSiteIds !== []) {
            event(new FrontendSurrogateKeysInvalidated(
                array_map(static fn (int $siteId): string => 'site-' . $siteId, $affectedSiteIds),
            ));
        }
    }

    /**
     * @param  list<int>  $widgetIds
     * @return list<int>
     */
    private function layoutsPlacing(array $widgetIds): array
    {
        if ($widgetIds === []) {
            return [];
        }

        $keys = Widget::withTrashed()->whereKey($widgetIds)->pluck('key')->filter(static fn (mixed $key): bool => is_string($key) && $key !== '')->all();

        if ($keys === []) {
            return [];
        }

        // One LIKE per widget key; chunked so a large migration never builds
        // a single query with thousands of OR clauses.
        $layoutIds = [];

        foreach (array_chunk(array_values($keys), self::LIKE_CHUNK) as $chunk) {
            $layoutIds = [...$layoutIds, ...Layout::withTrashed()
                ->where(static function (Builder $query) use ($chunk): void {
                    foreach ($chunk as $key) {
                        RetiredThemeReferenceScanner::whereLike($query, 'containers', '%"' . RetiredThemeReferenceScanner::escapeLike($key) . '"%', 'or');
                    }
                })
                ->pluck('id')
                ->all()];
        }

        return $this->ids($layoutIds);
    }

    /**
     * Sites whose own layouts, or whose pages on a shared global layout, are
     * among the given layouts.
     *
     * @param  list<int>  $layoutIds
     * @return list<int>
     */
    private function sitesRenderingThrough(array $layoutIds): array
    {
        if ($layoutIds === []) {
            return [];
        }

        return $this->ids([
            ...Layout::withTrashed()->whereKey($layoutIds)->whereNotNull('site_id')->pluck('site_id')->all(),
            ...Page::withTrashed()->whereIn('layout_id', $layoutIds)->whereNotNull('site_id')->pluck('site_id')->all(),
        ]);
    }

    /**
     * @param  list<int>  $widgetIds
     * @return list<int>
     */
    private function sitesWithPagesPlacing(array $widgetIds): array
    {
        $assets = TableName::resolve('widget_assets');

        if ($widgetIds === [] || ! Schema::hasTable($assets)) {
            return [];
        }

        $pageIds = DB::table($assets)
            ->whereIn('widget_id', $widgetIds)
            ->where('asset_type', new Page()->getMorphClass())
            ->pluck('asset_id')
            ->all();

        return $pageIds === [] ? [] : $this->ids(Page::withTrashed()->whereKey($pageIds)->whereNotNull('site_id')->pluck('site_id')->all());
    }

    /**
     * @param  list<int>  $siteIds
     * @param  list<int>  $layoutIds
     * @param  list<int>  $widgetLayoutIds
     */
    private function revokeWidgetSnapshots(?int $previousThemeId, array $siteIds, array $layoutIds, array $widgetLayoutIds): void
    {
        if (! CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName)
            || ! Schema::hasTable(new PublicWidgetSnapshot()->getTable())) {
            return;
        }

        if ($previousThemeId !== null && ($siteIds !== [] || $layoutIds !== [])) {
            PublicWidgetSnapshot::query()
                ->where('theme_id', $previousThemeId)
                ->whereNull('revoked_at')
                ->where(static function (Builder $query) use ($siteIds, $layoutIds): void {
                    $query->whereIn('site_id', $siteIds)->orWhereIn('layout_id', $layoutIds);
                })
                ->update(['revoked_at' => now(), 'current_key' => null]);
        }

        if ($widgetLayoutIds !== []) {
            PublicWidgetSnapshot::query()
                ->whereIn('layout_id', $widgetLayoutIds)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'current_key' => null]);
        }
    }

    /**
     * @param  array<mixed>  $values
     * @return list<int>
     */
    private function ids(array $values): array
    {
        $ids = [];

        foreach ($values as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }
}
