<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Actions\ResolveResultsListingAction;
use Capell\FoundationTheme\Data\ResultsListingData;
use Capell\FoundationTheme\Data\ResultsPaginationData;
use Capell\FoundationTheme\Data\ResultsPaginationRequestData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('paginates real directory content with honest totals and disjoint pages', function (): void {
    [$site, $language, $type] = resultsRecoveryFixture();
    $current = resultsRecoveryPage($site, $language, $type, 'Directory');
    foreach (range(1, 9) as $index) {
        resultsRecoveryPage($site, $language, $type, 'Guide ' . $index, [], $current);
    }

    resultsRecoveryPage($site, $language, $type, 'Recovery', ['theme_demo' => ['surface' => 'empty']], $current);

    $resolver = resolve(ResolveResultsListingAction::class);
    $first = $resolver->handlePaginated($site, $language, $current, new ResultsPaginationRequestData(page: 1, perPage: 6));
    $second = $resolver->handlePaginated($site, $language, $current, new ResultsPaginationRequestData(page: 2, perPage: 6));

    expect($first->items)->toHaveCount(6)
        ->and($second->items)->toHaveCount(3)
        ->and(resultsRecoveryPagination($first)->total)->toBe(9)
        ->and(resultsRecoveryPagination($first)->lastPage)->toBe(2)
        ->and(resultsRecoveryPagination($second)->currentPage)->toBe(2)
        ->and(array_intersect(array_column($first->items, 'url'), array_column($second->items, 'url')))->toBe([]);
    foreach (array_merge($first->items, $second->items) as $item) {
        expect($item['title'])->toStartWith('Guide ');
    }
});

it('omits demonstration recovery pages while retaining normal content with matching words', function (bool $children): void {
    [$site, $language, $type] = resultsRecoveryFixture();
    $current = resultsRecoveryPage($site, $language, $type, 'Gallery');
    $parent = $children ? $current : null;
    resultsRecoveryPage($site, $language, $type, 'No matches demo', ['theme_demo' => ['surface' => 'empty']], $parent);
    resultsRecoveryPage($site, $language, $type, 'Not found demo', ['theme_demo' => ['surface' => 'not-found']], $parent);
    resultsRecoveryPage($site, $language, $type, 'No matches', [], $parent);
    resultsRecoveryPage($site, $language, $type, 'Featured studio', ['theme_demo' => ['surface' => 'detail']], $parent);

    $results = resolve(ResolveResultsListingAction::class)->handle($site, $language, $current);

    expect(array_column($results->items, 'title'))->toContain('No matches', 'Featured studio')
        ->not->toContain('No matches demo')
        ->not->toContain('Not found demo')
        ->not->toContain('Gallery');
})->with(['site fallback' => false, 'children' => true]);

it('falls back to real site content when all children are demonstration recovery pages', function (): void {
    [$site, $language, $type] = resultsRecoveryFixture();
    $current = resultsRecoveryPage($site, $language, $type, 'Gallery');
    resultsRecoveryPage($site, $language, $type, 'No matches demo', ['theme_demo' => ['surface' => 'empty']], $current);
    resultsRecoveryPage($site, $language, $type, 'Published studio');

    $results = resolve(ResolveResultsListingAction::class)->handle($site, $language, $current);

    expect(array_column($results->items, 'title'))->toBe(['Published studio']);
});

it('fills the result limit with content instead of newer demonstration recovery pages', function (): void {
    [$site, $language, $type] = resultsRecoveryFixture();
    $current = resultsRecoveryPage($site, $language, $type, 'Gallery');
    $current->update(['visible_from' => now()->subYears(2)]);
    foreach (range(1, 24) as $index) {
        $page = resultsRecoveryPage($site, $language, $type, 'Studio ' . $index);
        $page->update(['visible_from' => now()->subDays(2)]);
    }

    foreach (range(1, 6) as $index) {
        resultsRecoveryPage($site, $language, $type, 'No matches demo ' . $index, ['theme_demo' => ['surface' => 'empty']]);
    }

    $results = resolve(ResolveResultsListingAction::class)->handle($site, $language, $current);

    expect($results->items)->toHaveCount(24);
    foreach ($results->items as $item) {
        expect($item['title'])->toStartWith('Studio ')
            ->and($item['publishedDate'])->toBeNull();
    }
});

it('keeps the selected child dataset on out of range pages and handles large page numbers', function (int $number): void {
    [$site, $language, $type] = resultsRecoveryFixture();
    $current = resultsRecoveryPage($site, $language, $type, 'Directory');
    resultsRecoveryPage($site, $language, $type, 'Child guide', [], $current);
    resultsRecoveryPage($site, $language, $type, 'Unrelated site guide');

    $results = resolve(ResolveResultsListingAction::class)->handlePaginated($site, $language, $current, new ResultsPaginationRequestData($number, 6));

    expect($results->items)->toBe([])
        ->and(resultsRecoveryPagination($results)->total)->toBe(1)
        ->and(resultsRecoveryPagination($results)->currentPage)->toBe($number)
        ->and(resultsRecoveryPagination($results)->lastPage)->toBe(1);
})->with([2, PHP_INT_MAX]);

it('counts fallback content after excluding the current directory and recovery children', function (): void {
    [$site, $language, $type] = resultsRecoveryFixture();
    $current = resultsRecoveryPage($site, $language, $type, 'Directory');
    resultsRecoveryPage($site, $language, $type, 'Recovery', ['theme_demo' => ['surface' => 'empty']], $current);
    resultsRecoveryPage($site, $language, $type, 'Fallback guide');
    [$foreignSite, $foreignLanguage, $foreignType] = resultsRecoveryFixture();
    resultsRecoveryPage($foreignSite, $foreignLanguage, $foreignType, 'Foreign guide');

    $results = resolve(ResolveResultsListingAction::class)->handlePaginated($site, $language, $current, new ResultsPaginationRequestData(1, 6));

    expect(array_column($results->items, 'title'))->toBe(['Fallback guide'])
        ->and(resultsRecoveryPagination($results)->total)->toBe(1);
});

it('preserves the legacy serialized results shape without pagination', function (): void {
    $results = new ResultsListingData([]);

    expect($results->toArray())->toBe(['items' => [], 'archiveUrl' => null]);
});

it('bounds full record hydration including canonical parents and renders loaded items without queries', function (): void {
    [$site, $language, $type] = resultsRecoveryFixture();
    $current = resultsRecoveryPage($site, $language, $type, 'Directory');
    foreach (range(1, 30) as $index) {
        resultsRecoveryPage($site, $language, $type, 'Guide ' . $index, [], $current);
    }

    $retrieved = 0;
    Page::retrieved(static function (Page $model) use (&$retrieved): void {
        // The existing loader enumerates ID projections before hydrating its window.
        // Count every other record, including the canonical parent eager load.
        if (array_keys($model->getAttributes()) !== ['id']) {
            $retrieved++;
        }
    });
    $results = resolve(ResolveResultsListingAction::class)->handlePaginated($site, $language, $current, new ResultsPaginationRequestData(3, 6));

    expect($results->items)->toHaveCount(6)
        ->and(resultsRecoveryPagination($results)->total)->toBe(30)
        ->and($retrieved)->toBeGreaterThanOrEqual(6)->toBeLessThanOrEqual(14);
    $queries = DB::pretend(function () use ($results): void {
        $html = view('capell-theme-foundation::theme.sections.content-listing', [
            'section' => (object) ['heading' => 'Directory', 'summary' => null, 'variant' => null, 'resolvedResults' => $results->toArray()],
        ])->render();
        foreach ($results->items as $item) {
            expect($html)->toContain(e($item['title']), e($item['url']));
        }

        expect($html)->not->toContain('authoring', 'preview=', 'wire:click');
    });

    expect($queries)->toBe([]);
});

/** @return array{Site, Language, Blueprint} */
function resultsRecoveryFixture(): array
{
    $language = Language::factory()->english()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $type = Blueprint::factory()->page()->create(['meta' => ['listable' => true]]);

    return [$site, $language, $type];
}

/** @param array<string, mixed> $meta */
function resultsRecoveryPage(Site $site, Language $language, Blueprint $type, string $title, array $meta = [], ?Page $parent = null): Page
{
    $factory = Page::factory()->site($site)->type($type)->published(CarbonImmutable::now()->subDay())
        ->withTranslations($language, ['title' => $title]);
    if ($parent instanceof Page) {
        $factory = $factory->parent($parent);
    }

    $page = $factory->create(['meta' => $meta]);
    PageUrl::factory()->site($site)->page($page)->language($language)->create(['url' => '/gallery-' . $page->getKey()]);

    return $page;
}

function resultsRecoveryPagination(ResultsListingData $results): ResultsPaginationData
{
    if (! $results->pagination instanceof ResultsPaginationData) {
        throw new LogicException('Expected pagination metadata from the paginated resolver.');
    }

    return $results->pagination;
}
