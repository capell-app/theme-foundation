<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Contracts\ResultsListingResolver;
use Capell\FoundationTheme\Data\ResultsListingData;
use Capell\FoundationTheme\Support\ResultsSectionContributor;
use Capell\LayoutBuilder\Models\Widget;

it('replaces authored listing items with hydrated results on the results layout', function (): void {
    $site = new Site;
    $language = new Language;
    $page = new Page;
    $layout = (new Layout)->forceFill(['key' => LayoutEnum::Results->value]);
    $widget = (new Widget)->forceFill([
        'meta' => [
            'type' => 'content-listing',
            'heading' => 'Search results',
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
});

it('preserves explicit listing items away from the results layout', function (): void {
    $site = new Site;
    $language = new Language;
    $page = new Page;
    $layout = (new Layout)->forceFill(['key' => LayoutEnum::Default->value]);
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
});

it('renders resolved results and preserves authored items as a fallback', function (string $variant): void {
    $section = (object) [
        'heading' => 'Results', 'summary' => null, 'variant' => null,
        'items' => [['title' => 'Authored item', 'url' => '/authored']],
        'resolvedResults' => ['items' => [['title' => 'Hydrated result', 'url' => '/hydrated']], 'archiveUrl' => '/archives'],
    ];
    /** @var view-string $view */
    $view = implode('', ['capell-theme-foundation::theme.sections.content-listing', $variant]);
    $resolvedHtml = view($view, compact('section'))->render();
    unset($section->resolvedResults);
    $fallbackHtml = view($view, compact('section'))->render();

    expect($resolvedHtml)->toContain('Hydrated result', 'href="/hydrated"', 'href="/archives"')
        ->not->toContain('Authored item')
        ->and($fallbackHtml)->toContain('Authored item', 'href="/authored"')
        ->not->toContain('Hydrated result', 'href="/archives"');
})->with(['default' => '', 'grid' => '--grid', 'rows' => '--rows', 'masonry' => '--masonry-safe']);
