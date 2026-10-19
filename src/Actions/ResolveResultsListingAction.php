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
use Capell\FoundationTheme\Contracts\PaginatesResultsListing;
use Capell\FoundationTheme\Data\ResultsListingData;
use Capell\FoundationTheme\Data\ResultsPaginationData;
use Capell\FoundationTheme\Data\ResultsPaginationRequestData;
use Capell\Frontend\Data\PageListingRequestData;
use Capell\Frontend\Support\Loader\PageLoader;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Override;
use UnexpectedValueException;

final readonly class ResolveResultsListingAction implements PaginatesResultsListing
{
    use AsFake;
    use AsObject;

    private const string BLOG_PACKAGE = 'capell-app/blog';

    public function __construct(
        private OptionalExtensionAvailability $availability,
        private ResolveResultsArchiveUrlAction $resolveArchiveUrl,
    ) {}

    #[Override]
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
            archiveUrl: $articleResults->isNotEmpty() ? $this->resolveArchiveUrl->handle($site, $language) : null,
        );
    }

    #[Override]
    public function handlePaginated(Site $site, Language $language, Page $page, ResultsPaginationRequestData $pagination): ResultsListingData
    {
        $perPage = max(1, min(48, $pagination->perPage));
        $currentPage = max(1, $pagination->page);
        $articleModel = $this->availability->packageAvailable(self::BLOG_PACKAGE)
            ? Relation::getMorphedModel('article') : null;
        $articleModel = is_string($articleModel)
            && is_a($articleModel, Model::class, true)
            && is_a($articleModel, Pageable::class, true) ? $articleModel : null;

        $articles = $articleModel === null ? null : fn (int $number, int $limit): LengthAwarePaginator => $this->paginatedResults(
            $site,
            $language,
            $number,
            $limit,
            morphModel: $articleModel,
        );
        $articleTotal = $articles === null ? 0 : $articles(1, 1)->total();
        $children = fn (int $number, int $limit): LengthAwarePaginator => $this->paginatedResults(
            $site,
            $language,
            $number,
            $limit,
            parent: $page,
        );
        $childTotal = $children(1, 1)->total();
        $pages = $childTotal > 0 ? $children : fn (int $number, int $limit): LengthAwarePaginator => $this->paginatedResults(
            $site,
            $language,
            $number,
            $limit,
            exclude: $page,
        );
        // An article alias pointing to Page already contains the page dataset.
        $pageTotal = $articleModel === Page::class ? 0 : ($childTotal > 0 ? $childTotal : $pages(1, 1)->total());
        $total = $articleTotal + $pageTotal;
        $lastPage = max(1, (int) ceil($total / $perPage));
        $items = new EloquentCollection;

        // Check before multiplying user input, including PHP_INT_MAX.
        if ($currentPage <= $lastPage) {
            $offset = ($currentPage - 1) * $perPage;
            if ($articles !== null && $offset < $articleTotal) {
                $items = $this->resultWindow($articles, $offset, min($perPage, $articleTotal - $offset), $perPage);
            }

            $remaining = $perPage - $items->count();
            if ($remaining > 0 && $pageTotal > 0) {
                $items = new EloquentCollection($items->concat($this->resultWindow(
                    $pages,
                    max(0, $offset - $articleTotal),
                    $remaining,
                    $perPage,
                ))->all());
            }
        }

        return new ResultsListingData(
            items: array_values($items->map($this->toItem(...))->values()->all()),
            archiveUrl: $articleTotal > 0 ? $this->resolveArchiveUrl->handle($site, $language) : null,
            pagination: new ResultsPaginationData($currentPage, $perPage, $total, $lastPage),
        );
    }

    /**
     * @param  non-empty-string|null  $morphModel
     * @return LengthAwarePaginator<int, Model&Pageable<Model>>
     */
    private function paginatedResults(Site $site, Language $language, int $number, int $limit, ?Page $parent = null, ?Page $exclude = null, ?string $morphModel = null): LengthAwarePaginator
    {
        $results = PageLoader::list(new PageListingRequestData(
            language: $language,
            site: $site,
            page: $parent,
            type: $parent instanceof Page ? 'children' : null,
            limit: $limit,
            paginationPage: $number,
            ordering: PageOrderEnum::Latest,
            withImage: true,
            withPagination: true,
            withDate: true,
            paginationKey: 'page',
            morphModel: $morphModel,
            useCache: false,
            modifyQuery: function (Builder $query) use ($exclude, $morphModel): void {
                if ($morphModel === null) {
                    $this->excludeRecoveryDemoPages($query);
                }

                if ($exclude instanceof Page) {
                    $query->whereKeyNot($exclude->getKey());
                }

                $query->orderBy($query->getModel()->getQualifiedKeyName());
            },
        ));

        if (! $results instanceof LengthAwarePaginator) {
            throw new UnexpectedValueException('Paginated page listings must return a length-aware paginator.');
        }

        return $results;
    }

    /**
     * @param  Closure(int, int): LengthAwarePaginator<int, Model&Pageable<Model>>  $load
     * @return EloquentCollection<int, Model&Pageable<Model>>
     */
    private function resultWindow(Closure $load, int $offset, int $count, int $perPage): EloquentCollection
    {
        $number = intdiv($offset, $perPage) + 1;
        $withinPage = $offset % $perPage;
        $items = $load($number, $perPage)->getCollection();
        if ($withinPage + $count > $perPage) {
            $items = $items->concat($load($number + 1, $perPage)->getCollection());
        }

        return new EloquentCollection($items->slice($withinPage, $count)->values()->all());
    }

    /** @return EloquentCollection<int, Model&Pageable<Model>> */
    private function pageResults(Site $site, Language $language, Page $page): EloquentCollection
    {
        $children = PageLoader::list(new PageListingRequestData(
            language: $language,
            site: $site,
            page: $page,
            type: 'children',
            ordering: PageOrderEnum::Latest,
            withImage: true,
            withDate: true,
            useCache: false,
            modifyQuery: $this->excludeRecoveryDemoPages(...),
        ));

        if ($children->isNotEmpty()) {
            return $children;
        }

        return PageLoader::list(new PageListingRequestData(
            language: $language,
            site: $site,
            limit: 24,
            ordering: PageOrderEnum::Latest,
            withImage: true,
            withDate: true,
            useCache: false,
            modifyQuery: $this->excludeRecoveryDemoPages(...),
        ))->reject(static fn (Pageable $result): bool => $result->getKey() === $page->getKey());
    }

    /** @param Builder<Model> $query */
    private function excludeRecoveryDemoPages(Builder $query): void
    {
        $surface = $query->getModel()->qualifyColumn('meta') . '->theme_demo->surface';

        $query->where(static function (Builder $query) use ($surface): void {
            $query->whereNull($surface)->orWhereNotIn($surface, ['empty', 'not-found']);
        });
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

        return PageLoader::list(new PageListingRequestData(
            language: $language,
            site: $site,
            limit: 24,
            ordering: PageOrderEnum::Latest,
            withImage: true,
            withDate: true,
            morphModel: $articleModel,
            useCache: false,
        ));
    }

    /** @return array{title: string, summary: string|null, url: string, type: string|null, image: string|null, publishedDate: string|null} */
    private function toItem(Pageable&Model $result): array
    {
        $translation = $result->relationLoaded('translation') ? $result->getRelation('translation') : null;
        $pageUrl = $result->relationLoaded('pageUrl') ? $result->getRelation('pageUrl') : null;
        $blueprint = $result->relationLoaded('blueprint') ? $result->getRelation('blueprint') : null;
        $image = $result->relationLoaded('image') ? $result->getRelation('image') : null;
        $publishedAt = $result->hasAttribute('published_at') ? $result->getAttribute('published_at') : null;
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

    private function resultKey(Pageable&Model $result): string
    {
        $key = $result->getKey();

        return $result::class . ':' . (is_int($key) || is_string($key) ? $key : 'unsaved');
    }
}
