<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\GenericSectionData;
use Capell\FoundationTheme\Testing\AssertsPublicThemeOutputSafety;
use Illuminate\Support\Facades\View;

/*
 * Wave 2.5 standard-section coverage: Foundation ships first-class views for
 * the 3 standard sections docs/theme-scale.md names but Foundation had not
 * shipped (search, pagination, form), registered the same way as the
 * existing 7 via FoundationThemeServiceProvider::themeStudioSectionRenderers().
 */

uses(AssertsPublicThemeOutputSafety::class);

beforeEach(function (): void {
    View::addNamespace('capell-theme-foundation', dirname(__DIR__, 2) . '/resources/views');
});

it('renders the search section view without error given minimal payload', function (): void {
    $section = new GenericSectionData('search', [
        'heading' => 'Search this site',
        'action' => '/search',
        'query' => 'capell',
        'results' => [
            ['title' => 'First result', 'summary' => 'First summary', 'url' => '/first-result'],
        ],
    ]);

    $html = view('capell-theme-foundation::theme.sections.search', $section->toViewData())->render();

    expect($html)
        ->toContain('Search this site')
        ->toContain('action="/search"')
        ->toContain('value="capell"')
        ->toContain('First result');
});

it('renders the search section view without a results payload', function (): void {
    $section = new GenericSectionData('search', ['heading' => 'Search this site']);

    $html = view('capell-theme-foundation::theme.sections.search', $section->toViewData())->render();

    expect($html)->toContain('Search this site');
});

it('renders the pagination section view without error given minimal payload', function (): void {
    $section = new GenericSectionData('pagination', [
        'currentPage' => 2,
        'totalPages' => 4,
        'baseUrl' => '/blog',
    ]);

    $html = view('capell-theme-foundation::theme.sections.pagination', $section->toViewData())->render();

    expect($html)
        ->toContain('rel="prev"')
        ->toContain('rel="next"')
        ->toContain('aria-current="page"')
        ->toContain('/blog?page=1')
        ->toContain('/blog?page=4');
});

it('renders the pagination section view on the first and last page', function (): void {
    $firstPage = new GenericSectionData('pagination', ['currentPage' => 1, 'totalPages' => 3, 'baseUrl' => '/blog']);
    $lastPage = new GenericSectionData('pagination', ['currentPage' => 3, 'totalPages' => 3, 'baseUrl' => '/blog']);

    $firstPageHtml = view('capell-theme-foundation::theme.sections.pagination', $firstPage->toViewData())->render();
    $lastPageHtml = view('capell-theme-foundation::theme.sections.pagination', $lastPage->toViewData())->render();

    expect($firstPageHtml)->not->toContain('rel="prev"');
    expect($lastPageHtml)->not->toContain('rel="next"');
});

it('renders the form section view without error given minimal payload', function (): void {
    $section = new GenericSectionData('form', [
        'heading' => 'Contact us',
        'action' => '/contact',
        'submitLabel' => 'Send message',
        'fields' => [
            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true],
            ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
            ['type' => 'textarea', 'name' => 'message', 'label' => 'Message'],
            ['type' => 'select', 'name' => 'topic', 'label' => 'Topic', 'options' => [
                ['value' => 'sales', 'label' => 'Sales'],
                ['value' => 'support', 'label' => 'Support'],
            ]],
            ['type' => 'checkbox', 'name' => 'subscribe', 'label' => 'Subscribe to updates'],
        ],
    ]);

    $html = view('capell-theme-foundation::theme.sections.form', $section->toViewData())->render();

    expect($html)
        ->toContain('Contact us')
        ->toContain('action="/contact"')
        ->toContain('Send message')
        ->toContain('name="name"')
        ->toContain('type="email"')
        ->toContain('<textarea')
        ->toContain('<select')
        ->toContain('type="checkbox"');
});

it('renders the form section view with no fields configured', function (): void {
    $section = new GenericSectionData('form', ['heading' => 'Contact us']);

    $html = view('capell-theme-foundation::theme.sections.form', $section->toViewData())->render();

    expect($html)->toContain('Contact us');
});

it('keeps the new search, pagination, and form section views free of authoring metadata and database access', function (): void {
    $this->assertThemeOutputMetadataIsSafe(
        dirname(__DIR__, 2) . '/resources/views/theme/sections',
        'capell-app/theme-foundation',
        exemptBannedTokens: ['Livewire', 'wire:'],
    );

    $sectionViews = [
        dirname(__DIR__, 2) . '/resources/views/theme/sections/search.blade.php',
        dirname(__DIR__, 2) . '/resources/views/theme/sections/pagination.blade.php',
        dirname(__DIR__, 2) . '/resources/views/theme/sections/form.blade.php',
    ];

    $databaseBannedTokens = ['::query(', 'DB::', 'loadMissing(', 'relationLoaded(', 'Frontend::', 'find('];

    foreach ($sectionViews as $sectionView) {
        $contents = file_get_contents($sectionView) ?: '';

        foreach ($databaseBannedTokens as $bannedToken) {
            expect($contents)->not->toContain($bannedToken, "{$sectionView} must not contain the banned database-access token \"{$bannedToken}\".");
        }
    }
});
