<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\ContentListingSectionData;
use Capell\Core\ThemeStudio\Data\CtaSectionData;
use Capell\Core\ThemeStudio\Data\HeroSectionData;
use Illuminate\Support\Facades\View;

/*
 * Wave 2.2 render coverage: every newly declared hero/content-listing/cta
 * variant view must render without error given minimal section data, using
 * the same view names FoundationThemeServiceProvider::themeStudioSectionRenderers()
 * wires into VariantViewSectionRenderer.
 */

beforeEach(function (): void {
    View::addNamespace('capell-theme-foundation', dirname(__DIR__, 2) . '/resources/views');
});

it('renders every declared hero variant view without error', function (string $view): void {
    $section = new HeroSectionData(
        heading: 'Foundation hero',
        summary: 'Minimal hero payload for variant render coverage.',
        actions: [['label' => 'Learn more', 'url' => '/learn-more']],
        mediaUrl: '/images/foundation-hero.jpg',
        mediaAlt: 'Preview image',
    );

    $html = view($view, ['section' => $section])->render();

    expect($html)
        ->toContain('Foundation hero')
        ->toContain('Learn more');
})->with([
    'base' => ['capell-theme-foundation::theme.sections.hero'],
    'split' => ['capell-theme-foundation::theme.sections.hero--split'],
    'stacked' => ['capell-theme-foundation::theme.sections.hero--stacked'],
    'full-bleed' => ['capell-theme-foundation::theme.sections.hero--full-bleed'],
]);

it('renders every declared content-listing variant view without error', function (string $view): void {
    $section = new ContentListingSectionData(
        heading: 'Latest content',
        summary: 'Minimal content-listing payload for variant render coverage.',
        items: [
            ['title' => 'First item', 'summary' => 'First summary', 'url' => '/first'],
            ['title' => 'Second item', 'summary' => 'Second summary', 'url' => '/second'],
            ['title' => 'Third item', 'summary' => 'Third summary', 'url' => '/third'],
        ],
    );

    $html = view($view, ['section' => $section])->render();

    expect($html)
        ->toContain('Latest content')
        ->toContain('First item');
})->with([
    'base' => ['capell-theme-foundation::theme.sections.content-listing'],
    'grid' => ['capell-theme-foundation::theme.sections.content-listing--grid'],
    'rows' => ['capell-theme-foundation::theme.sections.content-listing--rows'],
    'masonry-safe' => ['capell-theme-foundation::theme.sections.content-listing--masonry-safe'],
]);

it('renders every declared cta variant view without error', function (string $view): void {
    $section = new CtaSectionData(
        heading: 'Ready to get started?',
        summary: 'Minimal cta payload for variant render coverage.',
        actions: [['label' => 'Get started', 'url' => '/get-started']],
    );

    $html = view($view, ['section' => $section])->render();

    expect($html)
        ->toContain('Ready to get started?')
        ->toContain('Get started');
})->with([
    'base' => ['capell-theme-foundation::theme.sections.cta'],
    'band' => ['capell-theme-foundation::theme.sections.cta--band'],
    'card' => ['capell-theme-foundation::theme.sections.cta--card'],
    'inline' => ['capell-theme-foundation::theme.sections.cta--inline'],
]);

it('renders the search section with query status and an empty state', function (): void {
    $section = (object) [
        'heading' => 'Try another search',
        'summary' => 'Use broader terms.',
        'action' => '/search',
        'query' => 'missing page',
        'placeholder' => 'Search the archive',
        'results' => [],
    ];

    $html = view('capell-theme-foundation::theme.sections.search', ['section' => $section])->render();

    expect($html)
        ->toContain('Try another search')
        ->toContain('0 results for &quot;missing page&quot;')
        ->toContain('No matching results')
        ->toContain('Search the archive');
});
