<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Contracts;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Data\ResultsListingData;

interface ResultsListingResolver
{
    public function handle(Site $site, Language $language, Page $page): ResultsListingData;
}
