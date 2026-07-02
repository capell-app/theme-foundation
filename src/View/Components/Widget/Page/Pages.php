<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget\Page;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
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

        if ($morphModel !== null) {
            $resolvedModelClass = Relation::getMorphedModel($morphModel);

            if (is_string($resolvedModelClass) && is_subclass_of($resolvedModelClass, Pageable::class)) {
                /** @var class-string<Pageable<Model>> $resolvedModelClass */
                $modelClass = $resolvedModelClass;
            }
        }

        $this->pages = PageLoader::getPages(
            language: $language,
            site: Frontend::site(),
            page: $page,
            limit: $this->paginationLimit() ?? config('capell-frontend.pagination_limit', 12),
            paginationPage: $this->paginationPage('pages-card'),
            ordering: ($this->widget->meta['order'] ?? '') === '' ? null : PageOrderEnum::from($this->widget->meta['order']),
            pageGroup: $this->widget->meta['page_group'] ?? null,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withPagination: $this->paginationEnabled(),
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            paginationKey: 'pages-card',
            cacheKeyPrepend: 'pages-widget-' . $this->widget->id,
            morphModel: $modelClass,
            useCache: false,
            modifyQuery: function (Builder $query) use ($selection): void {
                $query->whereIn('id', $selection);
            },
        );

        if ($this->pages->isEmpty() && config('capell-layout-builder.widget.skip_render_empty', true) === true) {
            $this->skipRender = true;
        }
    }
}
