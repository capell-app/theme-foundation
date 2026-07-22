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

    $resolver = Mockery::mock(ResultsListingResolver::class);
    $resolver->shouldReceive('handle')
        ->once()
        ->with($site, $language, $page)
        ->andReturn($resolvedResults);

    $contributor = new ResultsSectionContributor($resolver);

    expect($contributor->contribute($widget, $layout, $site, $language, $page))->toBe($resolvedResults)
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

    $resolver = Mockery::mock(ResultsListingResolver::class);
    $resolver->shouldNotReceive('handle');

    $contributor = new ResultsSectionContributor($resolver);

    expect($contributor->contribute($widget, $layout, $site, $language, $page))->toBeNull()
        ->and(data_get($widget->meta, 'items.0.title'))->toBe('Authored landing item')
        ->and(data_get($widget->meta, 'resolvedResults'))->toBeNull();
});

it('binds every foundation content listing variant to resolved results with an explicit item fallback', function (): void {
    $views = [
        'content-listing.blade.php',
        'content-listing--grid.blade.php',
        'content-listing--rows.blade.php',
        'content-listing--masonry-safe.blade.php',
    ];

    foreach ($views as $view) {
        $source = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/sections/' . $view);

        expect($source)->not->toBeFalse()
            ->and($source)->toContain("data_get(\$section, 'resolvedResults.items', data_get(\$section, 'items', []))")
            ->and($source)->toContain("data_get(\$section, 'resolvedResults.archiveUrl')")
            ->and($source)->not->toContain('$section->items');
    }
});
