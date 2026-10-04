<?php

declare(strict_types=1);

it('does not invent a search query when rendering suggested results', function (): void {
    $html = view('capell-theme-foundation::theme.sections.search', ['section' => (object) [
        'heading' => 'Useful field notes',
        'results' => [
            ['title' => 'Planning a walk-through', 'url' => '/notes/walk-through'],
            ['title' => 'Writing a brief', 'url' => '/notes/brief'],
        ],
    ]])->render();

    expect($html)->toContain('2 results')
        ->toContain('Planning a walk-through')
        ->not->toContain('for &quot;Search&quot;')
        ->not->toContain('capell-theme-foundation::')
        ->not->toContain('wire:snapshot');
});

it('preserves the supplied query in a populated search summary', function (): void {
    $html = view('capell-theme-foundation::theme.sections.search', ['section' => (object) [
        'query' => 'workshop',
        'results' => [['title' => 'Shared workshop', 'url' => '/notes/workshop']],
    ]])->render();

    expect($html)->toContain('1 result for &quot;workshop&quot;')
        ->not->toContain('1 results')
        ->not->toContain('capell-theme-foundation::');
});

it('uses the plural form for several results and for none', function (): void {
    $several = view('capell-theme-foundation::theme.sections.search', ['section' => (object) [
        'query' => 'workshop',
        'results' => [['title' => 'One', 'url' => '/one'], ['title' => 'Two', 'url' => '/two']],
    ]])->render();
    $queryless = view('capell-theme-foundation::theme.sections.search', ['section' => (object) [
        'results' => [['title' => 'One', 'url' => '/one'], ['title' => 'Two', 'url' => '/two'], ['title' => 'Three', 'url' => '/three']],
    ]])->render();

    expect($several)->toContain('2 results for &quot;workshop&quot;')
        ->and($queryless)->toContain('3 results')
        ->not->toContain('for &quot;');
});
