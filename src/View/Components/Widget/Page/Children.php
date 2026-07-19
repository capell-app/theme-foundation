<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget\Page;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\FoundationTheme\Actions\PrepareFoundationPageWidgetDataAction;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Frontend\Support\Logging\FrontendLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Children extends AbstractPagesWidget
{
    protected static string $defaultView = 'capell-theme-foundation::components.widget.asset.pages';

    protected function mountWidget(): void
    {
        $preparedPages = Frontend::getFrontendData(PrepareFoundationPageWidgetDataAction::frontendDataKey($this->widget));

        if ($preparedPages instanceof Collection) {
            $this->pages = $preparedPages;
            $this->skipRender = $preparedPages->isEmpty();

            return;
        }

        $page = Frontend::page();
        $language = Frontend::language();

        if (! $page instanceof Pageable || ! $page instanceof Model || ! $language instanceof Language) {
            $this->skipRender = true;

            return;
        }

        if (! $page->hasPageHierarchy()) {
            $logger = resolve(FrontendLogger::class);

            $logger->warning('Frontend: page has no page hierarchy for children widget', [
                'pageable_type' => $page->getMorphClass(),
                'pageable_id' => $page->getKey(),
                'layout_id' => $page->layout?->key,
            ]);

            $this->skipRender = true;

            return;
        }

        if (isset($page->blueprint->meta['hidden']) && $page->blueprint->meta['hidden'] === true) {
            $this->skipRender = true;

            return;
        }

        $this->pages = PageLoader::getPages(
            language: $language,
            site: Frontend::site(),
            page: $page,
            type: 'children',
            ordering: PageOrderEnum::Alphabetical,
            withChildrenCount: $this->widget->meta['with_children_count'] ?? false,
            withImage: $this->widget->meta['with_image'] ?? false,
            withParent: $this->widget->meta['with_parent'] ?? false,
            withDate: $this->widget->meta['with_date'] ?? false,
            useCache: false,
        );

        if ($this->pages->isEmpty()) {
            $this->skipRender = true;
        }
    }
}
