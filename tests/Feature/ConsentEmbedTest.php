<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Contracts\ThemeRuntimeSettings;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Support\Themes\DeclaredEmbedOrigins;
use Capell\Frontend\Contracts\FrontendContextReader;
use Illuminate\Support\Facades\Blade;

beforeEach(function (): void {
    $registry = new ThemeRegistry;
    $registry->register(new ThemeDefinitionData(
        key: 'embed-fixture',
        name: 'Embed fixture',
        description: 'Embed fixture.',
        package: 'capell-app/theme-embed-fixture',
        previewImage: '/preview.jpg',
        tags: [],
        bestFit: [],
        presets: [],
        frontend: ['embedOrigins' => [
            'map-embed' => ['https://www.openstreetmap.org', 'http://insecure.example', 42],
            'video-embed' => ['https://www.youtube-nocookie.com/ignored-path'],
        ]],
    ));
    app()->instance(ThemeRegistry::class, $registry);
    app()->instance(ThemeRuntimeSettings::class, new readonly class implements ThemeRuntimeSettings
    {
        public function activeTheme(): string
        {
            return 'embed-fixture';
        }

        public function activePreset(): string
        {
            return '';
        }

        public function brandProfile(): BrandProfileData
        {
            return new BrandProfileData;
        }

        public function themeOverrides(): array
        {
            return [];
        }
    });
    app()->offsetUnset(FrontendContextReader::class);
    app()->forgetInstance(DeclaredEmbedOrigins::class);
});

it('reads only https origins the active theme declares for each widget', function (): void {
    $origins = resolve(DeclaredEmbedOrigins::class);

    expect($origins->for('map-embed'))->toBe(['https://www.openstreetmap.org'])
        ->and($origins->for('video-embed'))->toBe(['https://www.youtube-nocookie.com'])
        ->and($origins->for('unknown'))->toBe([])
        ->and($origins->permits('map-embed', 'https://WWW.openstreetmap.org/export/embed.html?bbox=1'))->toBeTrue()
        ->and($origins->permits('video-embed', 'https://www.openstreetmap.org/export'))->toBeFalse()
        ->and($origins->permits('map-embed', 'https://user@www.openstreetmap.org/'))->toBeFalse()
        ->and($origins->permits('map-embed', 'http://www.openstreetmap.org/'))->toBeFalse()
        ->and($origins->permits('map-embed', 'javascript:alert(1)'))->toBeFalse();
});

it('holds a declared embed behind a keyboard-operable consent button', function (): void {
    $html = Blade::render('<x-capell-theme-foundation::consent-embed widget="map-embed" src="https://www.openstreetmap.org/export/embed.html?bbox=1" title="Studio location" fallback-url="https://www.openstreetmap.org/#map=1" caption="Find us" />');

    expect($html)
        ->toContain('data-capell-consent-embed')
        ->toMatch('#<button\s+type="button"\s+class="capell-consent-embed__load"\s+data-capell-consent-embed-load\s*>#')
        ->toContain('Load Studio location')
        ->toContain('hosted by www.openstreetmap.org')
        ->toMatch('#<a\s+href="https://www.openstreetmap.org/\#map=1"#')
        ->toContain('<figcaption>Find us</figcaption>');

    $outsideTemplate = preg_replace('#<template\b.*?</template>#s', '', $html);

    expect(substr_count($html, '<iframe'))->toBe(1)
        ->and($outsideTemplate)->not->toContain('<iframe')
        ->and($html)->toMatch('#<template data-capell-consent-embed-template>\s*<iframe\s+src="https://www.openstreetmap.org/export/embed.html\?bbox=1"#');
});

it('never renders an embed from an origin the theme does not declare', function (): void {
    $html = Blade::render('<x-capell-theme-foundation::consent-embed widget="map-embed" src="https://tracker.example/embed" title="Studio location" fallback-url="https://tracker.example/map" />');

    expect($html)
        ->not->toContain('<iframe')
        ->not->toContain('<template')
        ->not->toContain('data-capell-consent-embed-load')
        ->not->toContain('tracker.example/embed')
        ->toContain('View Studio location on its own site')
        ->toContain('href="https://tracker.example/map"');
});

it('renders nothing when the origin is undeclared and there is no safe fallback', function (): void {
    $html = Blade::render('<x-capell-theme-foundation::consent-embed widget="video-embed" src="https://tracker.example/embed" title="Clip" fallback-url="javascript:alert(1)" />');

    expect(trim($html))->toBe('');
});
