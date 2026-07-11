<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Data\ResolvedResultsData;
use Capell\Frontend\Support\Loader\PageLoader;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Resolves a public, hydrated results listing before Blade renders.
 *
 * @method static ResolvedResultsData run(Site $site, Language $language, string $heading, ?string $summary = null)
 */
final class ResolveThemeResultsListingAction
{
    use AsObject;

    public function handle(Site $site, Language $language, string $heading, ?string $summary = null): ResolvedResultsData
    {
        $pages = PageLoader::getPages(
            language: $language,
            site: $site,
            limit: 24,
            withDate: true,
        );

        $items = $pages instanceof Collection
            ? $pages
                ->filter(fn (mixed $page): bool => $page instanceof Page)
                ->map(fn (Page $page): array => [
                    'title' => (string) ($page->relationLoaded('translation') ? $page->translation?->title : $page->name),
                    'summary' => is_string(data_get($page->relationLoaded('translation') ? $page->translation : null, 'meta.description'))
                        ? data_get($page->relationLoaded('translation') ? $page->translation : null, 'meta.description')
                        : null,
                    'url' => (string) ($page->relationLoaded('pageUrl') ? $page->pageUrl?->full_url : '#'),
                    'type' => $page->relationLoaded('type') && is_string($page->type?->name ?? null) ? $page->type->name : null,
                ])
                ->filter(fn (array $item): bool => $item['url'] !== '#')
                ->values()
                ->all()
            : [];

        return new ResolvedResultsData(
            heading: $heading,
            summary: $summary,
            items: $items,
        );
    }
}
