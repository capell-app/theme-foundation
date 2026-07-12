<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

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
| Rendered via Blade::render() with the compiled `<x-...>` tag syntax
| (rather than a bare view() call) because these are `@props`-based
| anonymous components: `$attributes` and prop/slot binding are only
| populated by Blade's component-tag compilation step, not by resolving
| the underlying view template directly.
*/

it('renders art-directed-picture without throwing', function (): void {
    $html = Blade::render(
        '<x-capell-theme-foundation::display.art-directed-picture :alt="$alt" :aspect-ratio="$aspectRatio" :focal-x="$focalX" :focal-y="$focalY" :sources="$sources" :src="$src" />',
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
    $html = Blade::render(
        '<x-capell-theme-foundation::display.hover-video-poster :alt="$alt" :poster="$poster" :video-src="$videoSrc" />',
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
    $html = Blade::render(
        '<x-capell-theme-foundation::display.card-frame-wrapper hover-effect="lift" variant="elevated">Card body content</x-capell-theme-foundation::display.card-frame-wrapper>',
    );

    expect($html)
        ->toContain('card-frame-wrapper-elevated')
        ->toContain('var(--foundation-card-bg)')
        ->toContain('Card body content');
});

it('renders responsive-table-to-cards without throwing', function (): void {
    $html = Blade::render(
        '<x-capell-theme-foundation::display.responsive-table-to-cards :headers="$headers" :rows="$rows" />',
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
        ->toContain('<table');
});

it('renders count-up-stat without throwing', function (): void {
    $html = Blade::render(
        '<x-capell-theme-foundation::display.count-up-stat :value="$value" :label="$label" suffix="+" />',
        [
            'label' => 'Happy customers',
            'value' => 4200,
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
    $html = Blade::render(
        '<x-capell-theme-foundation::display.byline-with-metadata :author-name="$authorName" :date="$date" :read-time="$readTime" />',
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
    $html = Blade::render(
        '<x-capell-theme-foundation::display.timestamp-metadata-block :datetime="$datetime" :label="$label" />',
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
    $html = Blade::render(
        '<x-capell-theme-foundation::display.photo-treatment-filter aspect-ratio="4/3"><img src="/images/mountain.jpg" alt="A mountain at dusk" /></x-capell-theme-foundation::display.photo-treatment-filter>',
    );

    expect($html)
        ->toContain('aspect-ratio: 4/3')
        ->toContain('filter: var(--foundation-photo-filter, none)')
        ->toContain('mix-blend-mode: var(--foundation-photo-tint-blend, normal)')
        ->toContain('/images/mountain.jpg');
});

it('is an inert no-op by default with all token fallbacks unset', function (): void {
    $html = Blade::render(
        '<x-capell-theme-foundation::display.photo-treatment-filter><img src="/images/mountain.jpg" alt="" /></x-capell-theme-foundation::display.photo-treatment-filter>',
    );

    expect($html)
        ->toContain('var(--foundation-photo-filter, none)')
        ->toContain('var(--foundation-photo-tint, transparent)')
        ->toContain('var(--foundation-photo-tint-opacity, 0)');
});

it('renders a cache-safe map link only for valid coordinates', function (): void {
    $html = Blade::render(
        '<x-capell-theme-foundation::display.map-link :latitude="$latitude" :longitude="$longitude" label="Find us" />',
        ['latitude' => 51.5074, 'longitude' => -0.1278],
    );

    $missingCoordinatesHtml = Blade::render(
        '<x-capell-theme-foundation::display.map-link :latitude="$latitude" :longitude="$longitude" />',
        ['latitude' => 91, 'longitude' => -0.1278],
    );

    expect($html)
        ->toContain('https://www.google.com/maps/search/?api=1&amp;query=51.5074%2C-0.1278')
        ->toContain('Find us')
        ->not->toContain('iframe')
        ->and($missingCoordinatesHtml)->toBeEmpty();
});
