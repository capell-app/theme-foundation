<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Capell\FoundationTheme\Contracts\ResultsListingResolver;
use Capell\FoundationTheme\Data\ResultsListingData;
use Capell\Frontend\Support\Loader\PageLoader;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final readonly class ResolveResultsListingAction implements ResultsListingResolver
{
    use AsFake;
    use AsObject;

    private const string BLOG_PACKAGE = 'capell-app/blog';

    public function __construct(private OptionalExtensionAvailability $availability) {}

    public function handle(Site $site, Language $language, Page $page): ResultsListingData
    {
        $pageResults = $this->pageResults($site, $language, $page);
        $articleResults = $this->articleResults($site, $language);

        return new ResultsListingData(
            items: array_values($articleResults
                ->concat($pageResults)
                ->unique($this->resultKey(...))
                ->take(24)
                ->map($this->toItem(...))
                ->values()
                ->all()),
            archiveUrl: $articleResults->isNotEmpty() ? $this->blogArchiveUrl($site, $language) : null,
        );
    }

    /** @return EloquentCollection<int, Model&Pageable<Model>> */
    private function pageResults(Site $site, Language $language, Page $page): EloquentCollection
    {
        $children = PageLoader::getPages(
            language: $language,
            site: $site,
            page: $page,
            type: 'children',
            ordering: PageOrderEnum::Latest,
            withDate: true,
            withImage: true,
            useCache: false,
        );

        if ($children->isNotEmpty()) {
            return $children;
        }

        return PageLoader::getPages(
            language: $language,
            site: $site,
            limit: 24,
            ordering: PageOrderEnum::Latest,
            withDate: true,
            withImage: true,
            useCache: false,
        )->reject(static fn (Pageable $result): bool => $result->getKey() === $page->getKey());
    }

    /** @return EloquentCollection<int, Model&Pageable<Model>> */
    private function articleResults(Site $site, Language $language): EloquentCollection
    {
        if (! $this->availability->packageAvailable(self::BLOG_PACKAGE)) {
            return new EloquentCollection;
        }

        $articleModel = Relation::getMorphedModel('article');

        if (! is_string($articleModel) || ! is_a($articleModel, Pageable::class, true)) {
            return new EloquentCollection;
        }

        return PageLoader::getPages(
            language: $language,
            site: $site,
            limit: 24,
            ordering: PageOrderEnum::Latest,
            withDate: true,
            withImage: true,
            morphModel: $articleModel,
            useCache: false,
        );
    }

    /** @return array{title: string, summary: string|null, url: string, type: string|null, image: string|null, publishedDate: string|null} */
    private function toItem(Pageable&Model $result): array
    {
        $translation = $result->relationLoaded('translation') ? $result->getRelation('translation') : null;
        $pageUrl = $result->relationLoaded('pageUrl') ? $result->getRelation('pageUrl') : null;
        $blueprint = $result->relationLoaded('blueprint') ? $result->getRelation('blueprint') : null;
        $image = $result->relationLoaded('image') ? $result->getRelation('image') : null;
        $publishedAt = $result->getAttribute('published_at');
        $name = $result->getAttribute('name');

        return [
            'title' => $translation instanceof Translation && is_string($translation->title)
                ? $translation->title
                : (is_string($name) ? $name : ''),
            'summary' => $translation instanceof Translation && is_string(data_get($translation, 'meta.description'))
                ? data_get($translation, 'meta.description')
                : null,
            'url' => $pageUrl instanceof PageUrl && is_string($pageUrl->full_url) ? $pageUrl->full_url : '#',
            'type' => $blueprint instanceof Blueprint && is_string($blueprint->name) ? $blueprint->name : null,
            'image' => is_string(data_get($image, 'original_url')) ? data_get($image, 'original_url') : null,
            'publishedDate' => $publishedAt instanceof DateTimeInterface ? $publishedAt->format('Y-m-d') : null,
        ];
    }

    private function blogArchiveUrl(Site $site, Language $language): ?string
    {
        $archivePage = Page::query()
            ->where('site_id', $site->getKey())
            ->whereHas('layout', static fn (Builder $query): Builder => $query->where('key', 'archives'))
            ->first();

        if (! $archivePage instanceof Page) {
            return null;
        }

        $archivePageUrl = PageUrl::query()
            ->where('pageable_type', Relation::getMorphAlias(Page::class))
            ->where('pageable_id', $archivePage->getKey())
            ->where('language_id', $language->getKey())
            ->first();

        return $archivePageUrl instanceof PageUrl && is_string($archivePageUrl->full_url)
            ? $archivePageUrl->full_url
            : null;
    }

    private function resultKey(Pageable&Model $result): string
    {
        $key = $result->getKey();

        return $result::class . ':' . (is_int($key) || is_string($key) ? $key : 'unsaved');
    }
}
