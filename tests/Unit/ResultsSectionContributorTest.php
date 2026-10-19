<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Contracts\PaginatesResultsListing;
use Capell\FoundationTheme\Contracts\ResultsListingResolver;
use Capell\FoundationTheme\Data\ResultsListingData;
use Capell\FoundationTheme\Data\ResultsPaginationData;
use Capell\FoundationTheme\Data\ResultsPaginationRequestData;
use Capell\FoundationTheme\Support\ResultsSectionContributor;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\RenderedHtml;
use Illuminate\Http\Request;

it('renders hydrated results for canonical and inherited results layouts', function (array $layoutAttributes): void {
    $site = new Site;
    $language = new Language;
    $page = new Page;
    $layout = (new Layout)->forceFill($layoutAttributes);
    $widget = (new Widget)->forceFill([
        'meta' => [
            'type' => 'content-listing',
            'heading' => 'Search results',
            'per_page' => 6,
            'items' => [['title' => 'Authored demo item']],
        ],
    ]);
    $resolvedResults = new ResultsListingData(
        items: [[
            'title' => 'Hydrated page',
            'summary' => 'Loaded before Blade renders.',
            'url' => '/hydrated-page',
            'type' => 'Page',
            'image' => null,
            'publishedDate' => '2026-07-19',
        ]],
        archiveUrl: '/blog/archives',
    );

    $resolver = new class($resolvedResults) implements ResultsListingResolver
    {
        public function __construct(private ResultsListingData $results) {}

        #[Override]
        public function handle(Site $site, Language $language, Page $page): ResultsListingData
        {
            return $this->results;
        }
    };

    $contributor = new ResultsSectionContributor($resolver);

    $results = $contributor->contribute($widget, $layout, $site, $language, $page);

    expect($results?->items)->toEqual($resolvedResults->items)
        ->and($results?->archiveUrl)->toBe('/blog/archives')
        ->and(data_get($widget->meta, 'items.0.title'))->toBe('Hydrated page')
        ->and(data_get($widget->meta, 'resolvedResults.archiveUrl'))->toBe('/blog/archives');

    $document = new RenderedHtml(view('capell-theme-foundation::theme.sections.content-listing', [
        'section' => (object) ['summary' => null, 'variant' => null, ...($widget->meta ?? [])],
    ])->render());

    expect($document->count('//a[@href="/hydrated-page"]'))->toBeGreaterThan(0)
        ->and($document->count('//a[@href="/blog/archives"]'))->toBe(1)
        ->and($document->count('//*[normalize-space(.)="Authored demo item"]'))->toBe(0);
})->with([
    'canonical' => [['key' => LayoutEnum::Results->value]],
    'installed theme surface' => [[
        'key' => 'theme-demo-knowledge-1-results-directory',
        'meta' => ['theme_demo' => ['base_layout' => LayoutEnum::Results->value]],
    ]],
]);

it('preserves explicit listing items away from the results layout', function (array $layoutAttributes): void {
    $site = new Site;
    $language = new Language;
    $page = new Page;
    $layout = (new Layout)->forceFill($layoutAttributes);
    $widget = (new Widget)->forceFill([
        'meta' => [
            'type' => 'content-listing',
            'items' => [['title' => 'Authored landing item']],
        ],
    ]);

    $resolver = new class implements ResultsListingResolver
    {
        #[Override]
        public function handle(Site $site, Language $language, Page $page): ResultsListingData
        {
            return new ResultsListingData(items: [['title' => 'Unrelated search result', 'summary' => null, 'url' => '/unrelated', 'type' => null, 'image' => null, 'publishedDate' => null]], archiveUrl: '/search');
        }
    };

    $contributor = new ResultsSectionContributor($resolver);

    expect($contributor->contribute($widget, $layout, $site, $language, $page))->toBeNull()
        ->and(data_get($widget->meta, 'items.0.title'))->toBe('Authored landing item')
        ->and(data_get($widget->meta, 'resolvedResults'))->toBeNull();
})->with([
    'canonical landing' => [['key' => LayoutEnum::Default->value]],
    'inherited landing' => [[
        'key' => 'theme-demo-knowledge-1-default-homepage',
        'meta' => ['theme_demo' => ['base_layout' => LayoutEnum::Default->value]],
    ]],
    'results-looking key without inheritance' => [['key' => 'custom-results-directory']],
]);

it('renders resolved results and preserves authored items as a fallback', function (string $variant): void {
    $section = (object) [
        'heading' => 'Results', 'summary' => null, 'variant' => null,
        'items' => [['title' => 'Authored item', 'url' => '/authored']],
        'resolvedResults' => ['items' => [['title' => 'Hydrated result', 'url' => '/hydrated']], 'archiveUrl' => '/archives'],
    ];
    /** @var view-string $view */
    $view = implode('', ['capell-theme-foundation::theme.sections.content-listing', $variant]);
    $resolvedHtml = view($view, ['section' => $section])->render();
    unset($section->resolvedResults);
    $fallbackHtml = view($view, ['section' => $section])->render();

    expect($resolvedHtml)->toContain('Hydrated result', 'href="/hydrated"', 'href="/archives"')
        ->not->toContain('Authored item')
        ->and($fallbackHtml)->toContain('Authored item', 'href="/authored"')
        ->not->toContain('Hydrated result', 'href="/archives"');
})->with(['default' => '', 'grid' => '--grid', 'rows' => '--rows', 'masonry' => '--masonry-safe']);

it('validates the GET page at the contributor boundary and opts into capable resolvers', function (string $query, int $expected): void {
    app()->instance('request', Request::create('/directory?' . $query));
    $resolver = new class implements PaginatesResultsListing
    {
        public ?ResultsPaginationRequestData $received = null;

        #[Override]
        public function handle(Site $site, Language $language, Page $page): ResultsListingData
        {
            throw new LogicException('Expected the paginated capability.');
        }

        #[Override]
        public function handlePaginated(Site $site, Language $language, Page $page, ResultsPaginationRequestData $pagination): ResultsListingData
        {
            $this->received = $pagination;

            return new ResultsListingData([], pagination: new ResultsPaginationData($pagination->page, $pagination->perPage, 10, 4));
        }
    };
    $widget = (new Widget)->forceFill(['meta' => ['type' => 'content-listing', 'per_page' => 3]]);
    $layout = (new Layout)->forceFill(['key' => LayoutEnum::Results->value]);

    $results = new ResultsSectionContributor($resolver)->contribute($widget, $layout, new Site, new Language, new Page);

    expect($resolver->received?->page)->toBe($expected)
        ->and($resolver->received?->perPage)->toBe(3)
        ->and(data_get($widget->meta, 'resolvedResults.pagination.currentPage'))->toBe($expected)
        ->and($results?->pagination)->toBeInstanceOf(ResultsPaginationData::class);
})->with([
    'second page' => ['page=2', 2],
    'zero' => ['page=0', 1],
    'negative' => ['page=-2', 1],
    'text' => ['page=invalid', 1],
    'array' => ['page[]=2', 1],
    'overflow' => ['page=999999999999999999999999999', 1],
]);
