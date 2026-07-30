<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Livewire\Widget;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\PageListingRequestData;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Override;

class Pages extends AbstractWidget
{
    use WithPagination;

    protected static string $defaultView = 'capell-theme-foundation::components.widget.asset.pages';

    /**
     * @var Collection<int, covariant mixed>|LengthAwarePaginator<int, covariant mixed>
     */
    protected Collection|LengthAwarePaginator $pages;

    #[Override]
    public function render(array $data = []): View|string|Closure
    {
        $data['pages'] = $this->pages;

        $view = parent::render($data);

        if ($view instanceof View) {
            return '<div class="contents">' . $view->render() . '</div>';
        }

        return $view;
    }

    protected function mountWidget(): void
    {
        $page = Frontend::page();
        $language = Frontend::language();
        $site = Frontend::site();
        $widget = $this->widget();

        if (! $page instanceof Pageable || ! $page instanceof Model || ! $language instanceof Language || ! $site instanceof Site) {
            $this->skipRender = true;

            return;
        }

        $configuredLimit = $widget->meta['limit'] ?? config('capell-frontend.pagination_limit', 12);
        $limit = is_numeric($configuredLimit) ? (int) $configuredLimit : 12;

        $paginationKey = $this->containerKey . ucfirst((string) $widget->key) . $this->occurrence;
        $paginationPage = (int) $this->getPage($paginationKey);

        $selection = $widget->assets->pluck('asset_id')->toArray();

        $morphModel = $widget->getMeta('page_model');

        $modelClass = null;

        if (is_string($morphModel)) {
            $resolvedModelClass = Relation::getMorphedModel($morphModel);

            if (is_string($resolvedModelClass) && is_subclass_of($resolvedModelClass, Pageable::class)) {
                /** @var class-string<Pageable<Model>> $resolvedModelClass */
                $modelClass = $resolvedModelClass;
            }
        }

        $this->pages = PageLoader::list(new PageListingRequestData(
            language: $language,
            site: $site,
            page: $page,
            limit: $limit,
            paginationPage: $paginationPage,
            ordering: is_string($widget->meta['order'] ?? null)
                ? PageOrderEnum::tryFrom($widget->meta['order'])
                : null,
            pageGroup: is_string($widget->meta['page_group'] ?? null)
                ? $widget->meta['page_group']
                : null,
            withChildrenCount: (bool) ($widget->meta['with_children_count'] ?? false),
            withImage: (bool) ($widget->meta['with_image'] ?? false),
            withPagination: (bool) ($widget->meta['pagination'] ?? false),
            withParent: (bool) ($widget->meta['with_parent'] ?? false),
            withDate: (bool) ($widget->meta['with_date'] ?? false),
            paginationKey: $paginationKey,
            morphModel: $modelClass,
            useCache: false,
            modifyQuery: function (Builder $query) use ($selection): void {
                $query->whereIn('id', $selection);
            },
        ));

        if ($this->pages->isEmpty() && config('capell-layout-builder.widget.skip_render_empty', true) === true) {
            $this->skipRender = true;
        }
    }
}
