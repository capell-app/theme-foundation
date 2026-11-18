<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ResolveHeroHeadingLevelAction;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Tests\Support\RenderedDocument;

beforeEach(function (): void {
    resolve(FrontendContextReader::class)->setFrontendData('has_primary_heading', false);
});

it('gives the first heading the h1 and every later one an h2', function (): void {
    expect(ResolveHeroHeadingLevelAction::run())->toBe('h1')
        ->and(ResolveHeroHeadingLevelAction::run())->toBe('h2')
        ->and(ResolveHeroHeadingLevelAction::run())->toBe('h2');
});

it('does not let a hero without a heading take the h1', function (): void {
    expect(ResolveHeroHeadingLevelAction::run(null, false))->toBe('h1')
        ->and(ResolveHeroHeadingLevelAction::run())->toBe('h1')
        ->and(ResolveHeroHeadingLevelAction::run())->toBe('h2');
});

it('honours a configured level and only an h1 claims the page heading', function (): void {
    expect(ResolveHeroHeadingLevelAction::run('h3'))->toBe('h3')
        ->and(ResolveHeroHeadingLevelAction::run('h2'))->toBe('h2')
        ->and(ResolveHeroHeadingLevelAction::run())->toBe('h1')
        ->and(ResolveHeroHeadingLevelAction::run('h1'))->toBe('h1');
});

it('renders one h1 across a composed page of heroes', function (): void {
    $section = (object) [
        'eyebrow' => '', 'heading' => 'Hero heading', 'summary' => 'Summary',
        'mediaUrl' => '/media/hero.jpg', 'mediaAlt' => 'Photograph',
        'actions' => [],
    ];
    $first = view('capell-theme-foundation::theme.sections.hero', ['section' => $section])->render();
    $second = view('capell-theme-foundation::theme.sections.hero--split', ['section' => $section])->render();

    $first = new RenderedDocument($first);
    $second = new RenderedDocument($second);

    expect($first->elements('//h1'))->toHaveCount(1)
        ->and($first->elements('//h2'))->toHaveCount(0)
        ->and($second->elements('//h1'))->toHaveCount(0)
        ->and($second->elements('//h2[normalize-space(.)="Hero heading"]'))->toHaveCount(1);
});

it('leaves heroes outside the themes alone so they cannot starve a theme hero of the h1', function (): void {
    view()->addNamespace('capell-package-hero', dirname(__DIR__, 2) . '/tests/Fixtures/views/section-variants');

    /** @var view-string $view */
    $view = 'capell-package-hero::sections.hero';

    expect(view($view, ['section' => (object) []])->render())->toContain('hero')
        ->and(ResolveHeroHeadingLevelAction::run())->toBe('h1');
});
