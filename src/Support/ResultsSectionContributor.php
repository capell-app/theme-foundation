<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Contracts\ResultsListingResolver;
use Capell\FoundationTheme\Data\ResultsListingData;
use Capell\LayoutBuilder\Models\Widget;

final readonly class ResultsSectionContributor
{
    public function __construct(private ResultsListingResolver $resolveResultsListing) {}

    public function contribute(Widget $widget, Layout $layout, Site $site, Language $language, Page $page): ?ResultsListingData
    {
        $meta = is_array($widget->meta) ? $widget->meta : [];

        if (($meta['type'] ?? null) !== 'content-listing' || $layout->key !== LayoutEnum::Results->value) {
            return null;
        }

        $results = $this->resolveResultsListing->handle($site, $language, $page);
        $widget->setAttribute('meta', [
            ...$meta,
            'items' => $results->items,
            'resolvedResults' => $results->toArray(),
        ]);

        return $results;
    }
}
