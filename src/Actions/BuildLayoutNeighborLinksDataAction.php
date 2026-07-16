<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Data\LayoutNeighborLinksData;
use Capell\Frontend\Support\Loader\PageLoader;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildLayoutNeighborLinksDataAction
{
    use AsFake;
    use AsObject;

    public function handle(Pageable $page, Site $site, Language $language): LayoutNeighborLinksData
    {
        if (! (bool) $page->getMeta('with_next_prev') || (bool) $page->getMeta('suppress_layout_neighbor_links')) {
            return new LayoutNeighborLinksData(previousPage: null, nextPage: null);
        }

        return new LayoutNeighborLinksData(
            previousPage: PageLoader::getPreviousPage($page, $site, $language),
            nextPage: PageLoader::getNextPage($page, $site, $language),
        );
    }
}
