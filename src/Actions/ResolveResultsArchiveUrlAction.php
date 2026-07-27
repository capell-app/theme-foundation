<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class ResolveResultsArchiveUrlAction
{
    public function handle(Site $site, Language $language): ?string
    {
        $archivePage = Page::query()
            ->where('site_id', $site->getKey())
            ->published()
            ->whereHas('layout', static fn (Builder $query): Builder => $query->where('key', 'archives'))
            ->whereHas('pageUrls', static fn (Builder $query): Builder => $query
                ->where('language_id', $language->getKey())
                ->where('status', true))
            ->orderBy('id')
            ->first();

        if (! $archivePage instanceof Page) {
            return null;
        }

        $archivePageUrl = PageUrl::query()
            ->where('pageable_type', Relation::getMorphAlias(Page::class))
            ->where('pageable_id', $archivePage->getKey())
            ->where('language_id', $language->getKey())
            ->where('status', true)
            ->orderBy('id')
            ->first();

        return $archivePageUrl instanceof PageUrl && is_string($archivePageUrl->full_url)
            ? $archivePageUrl->full_url
            : null;
    }
}
