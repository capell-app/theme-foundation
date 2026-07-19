<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

final class PrepareFoundationPageWidgetDataAction
{
    use AsFake;
    use AsObject;

    public function __construct(private readonly Request $request) {}

    public static function frontendDataKey(Widget $widget): string
    {
        return 'foundation.widget.pages.' . self::widgetKey($widget);
    }

    /** @param callable(string, mixed): mixed $setFrontendData */
    public function handle(Site $site, Language $language, Page $page, callable $setFrontendData): void
    {
        $widgets = CapellLayoutManager::getContainerWidgets()
            ->flatten(2)
            ->filter(static fn (mixed $widget): bool => $widget instanceof Widget)
            ->unique(static fn (Widget $widget): int|string => self::widgetKey($widget));

        foreach ($widgets as $widget) {
            $pages = match ($widget->key) {
                'children' => $this->children($widget, $site, $language, $page),
                'latest-pages' => $this->latest($widget, $site, $language, $page),
                default => null,
            };

            if ($pages !== null) {
                $setFrontendData(self::frontendDataKey($widget), $pages);
            }
        }
    }

    private static function widgetKey(Widget $widget): int|string
    {
        $key = $widget->getKey();
        throw_unless(is_int($key) || is_string($key), RuntimeException::class, 'Widget must have a scalar key.');

        return $key;
    }

    /** @return Collection<int, covariant mixed> */
    private function children(Widget $widget, Site $site, Language $language, Page $page): Collection
    {
        if (! $page->hasPageHierarchy() || data_get($page->blueprint?->meta, 'hidden') === true) {
            return collect();
        }

        return PageLoader::getPages(
            language: $language,
            site: $site,
            page: $page,
            type: 'children',
            ordering: PageOrderEnum::Alphabetical,
            withChildrenCount: (bool) $widget->getMeta('with_children_count', false),
            withImage: (bool) $widget->getMeta('with_image', false),
            withParent: (bool) $widget->getMeta('with_parent', false),
            withDate: (bool) $widget->getMeta('with_date', false),
            useCache: false,
        );
    }

    private function latest(Widget $widget, Site $site, Language $language, Page $page): mixed
    {
        $pagination = (bool) $widget->getMeta('pagination', false);
        $limit = $widget->getMeta('limit');
        $configuredLimit = config('capell-frontend.pagination_limit', 12);
        $limit = is_numeric($limit) ? (int) $limit : (is_numeric($configuredLimit) ? (int) $configuredLimit : 12);
        $morphModel = $widget->getMeta('page_model');
        $modelClass = is_string($morphModel) ? Relation::getMorphedModel($morphModel) : null;
        $pageGroup = $widget->getMeta('page_group');

        return PageLoader::getPages(
            language: $language,
            site: $site,
            page: $page,
            limit: $limit,
            paginationPage: $pagination ? max(1, $this->request->integer('latest-pages', $this->request->integer('page', 1))) : null,
            ordering: PageOrderEnum::Latest,
            pageGroup: is_string($pageGroup) ? $pageGroup : null,
            withChildrenCount: (bool) $widget->getMeta('with_children_count', false),
            withImage: (bool) $widget->getMeta('with_image', false),
            withPagination: $pagination,
            withParent: (bool) $widget->getMeta('with_parent', false),
            withDate: (bool) $widget->getMeta('with_date', false),
            paginationKey: 'latest-pages',
            cacheKeyPrepend: 'latest-widget-' . self::widgetKey($widget),
            morphModel: is_string($modelClass) && is_a($modelClass, Pageable::class, true) ? $modelClass : null,
            useCache: false,
            modifyQuery: static function (Builder $query) use ($page): void {
                $query->whereKeyNot($page->getKey());
            },
        );
    }
}
