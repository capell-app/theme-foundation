<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Livewire\Blaze\Blaze;

/**
 * @param  view-string  $view
 * @param  array<string, mixed>  $data
 */
function renderFoundationPrimitive(string $view, array $data = [], string $slot = ''): string
{
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        return view($view, [
            ...$data,
            'attributes' => new ComponentAttributeBag,
            'slot' => new ComponentSlot($slot),
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Display primitives render (Wave 2.7)
|--------------------------------------------------------------------------
|
| One render-without-throwing test per shared Foundation display primitive,
| each given minimal sample props. These are pure presentational Blade
| components (no DB/facade calls), so the assertion is deliberately light:
| the view resolves and renders to a non-empty string containing an
| expected fragment, proving the prop contract works end to end.
|
| Rendered via renderFoundationPrimitive() with the compiled `<x-...>` tag syntax
| (rather than a bare view() call) because these are `@props`-based
| anonymous components: `$attributes` and prop/slot binding are only
| populated by Blade's component-tag compilation step, not by resolving
| the underlying view template directly.
*/

it('renders art-directed-picture without throwing', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.art-directed-picture',
        [
            'alt' => 'A mountain at dusk',
            'aspectRatio' => '16/9',
            'focalX' => '30%',
            'focalY' => '60%',
            'sources' => [
                ['media' => '(min-width: 768px)', 'srcset' => '/images/mountain-wide.jpg'],
            ],
            'src' => '/images/mountain.jpg',
        ],
    );

    expect($html)
        ->toContain('<picture')
        ->toContain('/images/mountain.jpg')
        ->toContain('--focal-x: 30%');
});

it('renders hover-video-poster without throwing', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.hover-video-poster',
        [
            'alt' => 'Studio walkthrough',
            'poster' => '/images/studio-poster.jpg',
            'videoSrc' => '/videos/studio.mp4',
        ],
    );

    expect($html)
        ->toContain('/images/studio-poster.jpg')
        ->toContain('/videos/studio.mp4')
        ->toContain('muted');
});

it('renders card-frame-wrapper without throwing', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.card-frame-wrapper',
        ['hoverEffect' => 'lift', 'variant' => 'elevated'],
        'Card body content',
    );

    expect($html)
        ->toContain('card-frame-wrapper-elevated')
        ->toContain('var(--foundation-card-bg)')
        ->toContain('Card body content');
});

it('renders responsive-table-to-cards without throwing', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.responsive-table-to-cards',
        [
            'headers' => ['Name', 'Role'],
            'rows' => [
                ['Ada Lovelace', 'Mathematician'],
                ['Grace Hopper', 'Rear Admiral'],
            ],
        ],
    );

    expect($html)
        ->toContain('Ada Lovelace')
        ->toContain('Grace Hopper')
        ->toContain('<table')
        ->toContain('<dl')
        ->toContain('<dt>Name</dt>')
        ->toContain('<dd>Ada Lovelace</dd>')
        ->toContain('scope="col"');
});

it('renders an accessible responsive table caption and intentional empty state', function (): void {
    $tableHtml = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.responsive-table-to-cards',
        [
            'caption' => 'Project roles',
            'headers' => ['Name'],
            'rows' => [['Ada Lovelace']],
        ],
    );
    $emptyHtml = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.responsive-table-to-cards',
        [
            'emptyTitle' => 'No project roles',
            'emptyDescription' => 'Add the first role to begin.',
        ],
    );

    expect($tableHtml)
        ->toContain('<caption>')
        ->toContain('Project roles')
        ->toContain('role="region"')
        ->toContain('aria-label="Project roles"')
        ->and($emptyHtml)
        ->toContain('No project roles')
        ->toContain('Add the first role to begin.')
        ->not->toContain('<table');
});

it('renders safe button loading, disabled, external, and invalid-url states', function (): void {
    $loadingButton = Blade::render(<<<'BLADE'
        <x-capell::button type="submit" color="primary" loading>
            Save changes
        </x-capell::button>
        BLADE);
    $disabledLink = Blade::render(<<<'BLADE'
        <x-capell::button url="/next" disabled>
            Continue
        </x-capell::button>
        BLADE);
    $externalLink = Blade::render(<<<'BLADE'
        <x-capell::button url="https://example.test" target="_blank">
            External resource
        </x-capell::button>
        BLADE);
    $unsafeUrl = Blade::render(<<<'BLADE'
        <x-capell::button url="javascript:alert(1)">
            Unsafe resource
        </x-capell::button>
        BLADE);

    expect($loadingButton)
        ->toContain('<button')
        ->toContain('type="submit"')
        ->toContain('disabled')
        ->toContain('aria-busy="true"')
        ->toContain('Loading')
        ->and($disabledLink)
        ->toContain('aria-disabled="true"')
        ->not->toContain('href="/next"')
        ->and($externalLink)
        ->toContain('href="https://example.test"')
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer"')
        ->and($unsafeUrl)
        ->toContain('<button')
        ->not->toContain('javascript:');
});

it('renders count-up-stat without throwing', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.count-up-stat',
        [
            'label' => 'Happy customers',
            'statValue' => 4200,
            'suffix' => '+',
        ],
    );

    expect($html)
        ->toContain('data-count-up="4200"')
        ->toContain('Happy customers')
        ->toContain('data-count-up-suffix="+"')
        ->toContain('4,200+')
        ->toContain('</span');
});

it('renders byline-with-metadata without throwing', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.byline-with-metadata',
        [
            'authorName' => 'Jamie Rivers',
            'date' => '3 July 2026',
            'readTime' => '5 min read',
        ],
    );

    expect($html)
        ->toContain('Jamie Rivers')
        ->toContain('3 July 2026')
        ->toContain('5 min read');
});

it('renders timestamp-metadata-block without throwing', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.timestamp-metadata-block',
        [
            'datetime' => '2026-07-03T09:00:00+00:00',
            'label' => '3 July 2026',
        ],
    );

    expect($html)
        ->toContain('<time')
        ->toContain('datetime="2026-07-03T09:00:00+00:00"')
        ->toContain('3 July 2026');
});

it('renders photo-treatment-filter without throwing', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.photo-treatment-filter',
        ['aspectRatio' => '4/3'],
        '<img src="/images/mountain.jpg" alt="A mountain at dusk" />',
    );

    expect($html)
        ->toContain('aspect-ratio: 4/3')
        ->toContain('filter: var(--foundation-photo-filter, none)')
        ->toContain('mix-blend-mode: var(--foundation-photo-tint-blend, normal)')
        ->toContain('/images/mountain.jpg');
});

it('is an inert no-op by default with all token fallbacks unset', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.photo-treatment-filter',
        [],
        '<img src="/images/mountain.jpg" alt="" />',
    );

    expect($html)
        ->toContain('var(--foundation-photo-filter, none)')
        ->toContain('var(--foundation-photo-tint, transparent)')
        ->toContain('var(--foundation-photo-tint-opacity, 0)');
});

it('renders a cache-safe map link only for valid coordinates', function (): void {
    $html = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.map-link',
        ['label' => 'Find us', 'latitude' => 51.5074, 'longitude' => -0.1278],
    );

    $missingCoordinatesHtml = renderFoundationPrimitive(
        'capell-theme-foundation::components.display.map-link',
        ['latitude' => 91, 'longitude' => -0.1278],
    );

    expect($html)
        ->toContain('https://www.google.com/maps/search/?api=1&amp;query=51.5074%2C-0.1278')
        ->toContain('Find us')
        ->not->toContain('iframe')
        ->and($missingCoordinatesHtml)->toBeEmpty();
});
