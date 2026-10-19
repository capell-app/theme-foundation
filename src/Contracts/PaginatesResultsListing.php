<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Contracts;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Data\ResultsListingData;
use Capell\FoundationTheme\Data\ResultsPaginationRequestData;

interface PaginatesResultsListing extends ResultsListingResolver
{
    public function handlePaginated(Site $site, Language $language, Page $page, ResultsPaginationRequestData $pagination): ResultsListingData;
}
