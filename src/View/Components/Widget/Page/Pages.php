<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget\Page;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Frontend\Data\PageListingRequestData;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Pages extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-theme-foundation::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        $page = Frontend::page();
        $language = Frontend::language();

        if (! $language instanceof Language) {
            $this->skipRender = true;

            return;
        }

        $selection = $this->widget->assets()->pluck('asset_id')->all();

        $morphModel = $this->widget->getMeta('page_model');

        $modelClass = null;

        if (is_string($morphModel)) {
            $resolvedModelClass = Relation::getMorphedModel($morphModel);

            if (is_string($resolvedModelClass) && is_subclass_of($resolvedModelClass, Pageable::class)) {
                /** @var class-string<Pageable<Model>> $resolvedModelClass */
                $modelClass = $resolvedModelClass;
            }
        }

        $configuredLimit = $this->paginationLimit() ?? config('capell-frontend.pagination_limit', 12);
        $limit = is_numeric($configuredLimit) ? (int) $configuredLimit : 12;

        $this->pages = PageLoader::list(new PageListingRequestData(
            language: $language,
            site: Frontend::site(),
            page: $page,
            limit: $limit,
            paginationPage: $this->paginationPage('pages-card'),
            ordering: is_string($this->widget->meta['order'] ?? null)
                ? PageOrderEnum::tryFrom($this->widget->meta['order'])
                : null,
            pageGroup: is_string($this->widget->meta['page_group'] ?? null)
                ? $this->widget->meta['page_group']
                : null,
            withChildrenCount: (bool) ($this->widget->meta['with_children_count'] ?? false),
            withImage: (bool) ($this->widget->meta['with_image'] ?? false),
            withPagination: $this->paginationEnabled(),
            withParent: (bool) ($this->widget->meta['with_parent'] ?? false),
            withDate: (bool) ($this->widget->meta['with_date'] ?? false),
            paginationKey: 'pages-card',
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
