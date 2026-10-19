<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Actions\ResolveResultsPaginationLinksAction;
use Capell\FoundationTheme\Contracts\PaginatesResultsListing;
use Capell\FoundationTheme\Contracts\ResultsListingResolver;
use Capell\FoundationTheme\Data\ResultsListingData;
use Capell\FoundationTheme\Data\ResultsPaginationData;
use Capell\FoundationTheme\Data\ResultsPaginationRequestData;
use Capell\LayoutBuilder\Models\Widget;

final readonly class ResultsSectionContributor
{
    public function __construct(private ResultsListingResolver $resolveResultsListing) {}

    public function contribute(Widget $widget, Layout $layout, Site $site, Language $language, Page $page): ?ResultsListingData
    {
        $meta = is_array($widget->meta) ? $widget->meta : [];
        $isResultsLayout = $layout->key === LayoutEnum::Results->value
            || data_get($layout->meta, 'theme_demo.base_layout') === LayoutEnum::Results->value;

        if (($meta['type'] ?? null) !== 'content-listing' || ! $isResultsLayout) {
            return null;
        }

        $perPage = filter_var($meta['per_page'] ?? null, FILTER_VALIDATE_INT);
        $results = $perPage !== false && $perPage > 0 && $this->resolveResultsListing instanceof PaginatesResultsListing
            ? $this->resolveResultsListing->handlePaginated($site, $language, $page, new ResultsPaginationRequestData(
                page: max(1, filter_var(request()->query('page', 1), FILTER_VALIDATE_INT) ?: 1),
                perPage: $perPage,
            ))
            : $this->resolveResultsListing->handle($site, $language, $page);
        if ($results->pagination instanceof ResultsPaginationData) {
            $results = new ResultsListingData(
                $results->items,
                $results->archiveUrl,
                ResolveResultsPaginationLinksAction::run($results->pagination, request()->url()),
            );
        }

        $widget->setAttribute('meta', [
            ...$meta,
            'items' => $results->items,
            'resolvedResults' => $results->toArray(),
        ]);

        return $results;
    }
}
