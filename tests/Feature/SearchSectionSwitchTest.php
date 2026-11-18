<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\SearchSwitch;

function foundationSearchSectionHtml(): string
{
    return view('capell-theme-foundation::theme.sections.search', ['section' => (object) [
        'action' => '/search',
        'results' => [['title' => 'Planning a walk-through', 'url' => '/notes/walk-through']],
    ]])->render();
}

it('renders the search form while public search is switched on', function (): void {
    config(['capell-search.enabled' => true]);
    app()->forgetScopedInstances();

    expect(foundationSearchSectionHtml())->toContain('role="search"');
});

it('drops the form that posts to the results page while public search is switched off', function (): void {
    config(['capell-search.enabled' => false]);
    app()->forgetScopedInstances();

    expect(resolve(SearchSwitch::class)->blocksAction('/search'))->toBeTrue()
        ->and(foundationSearchSectionHtml())
        ->not->toContain('role="search"')
        ->toContain('Planning a walk-through');
});

it('drops the whole section, heading included, when search is off and there is nothing else to show', function (): void {
    config(['capell-search.enabled' => false]);
    app()->forgetScopedInstances();

    $html = view('capell-theme-foundation::theme.sections.search', ['section' => (object) [
        'action' => '/search',
        'heading' => 'Find a note',
        'summary' => 'Search the notes.',
    ]])->render();

    expect(trim($html))->toBe('');
});
