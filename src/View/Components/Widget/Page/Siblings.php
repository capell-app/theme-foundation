<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget\Page;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;

class Siblings extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-theme-foundation::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        $page = Frontend::page();
        $language = Frontend::language();

        if (! $page instanceof Pageable || ! $page instanceof Model || ! $language instanceof Language) {
            $this->skipRender = true;

            return;
        }

        if (isset($page->type->meta['hidden']) && $page->type->meta['hidden'] === true) {
            $this->skipRender = true;

            return;
        }

        $parentId = $page->getAttribute('parent_id');

        if ($parentId === null) {
            $this->skipRender = true;

            return;
        }

        $this->pages = PageLoader::getPages(
            language: $language,
            site: Frontend::site(),
            page: $page,
            type: 'siblings',
            ordering: PageOrderEnum::Alphabetical,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            cacheKeyPrepend: 'page-not-' . $page->getKey(),
            useCache: false,
            modifyQuery: function (BuilderContract $query) use ($page): void {
                $query->whereKeyNot($page->getKey());
            },
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
