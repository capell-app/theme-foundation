<?php

declare(strict_types=1);

use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Support\Demo\ThemeDemoMedia;

it('normalizes theme demo install data', function (): void {
    $data = new ThemeDemoInstallData(
        siteNames: ['Demo Site', ''],
        languageCodes: ['en', 'cy'],
        baseUrl: 'https://demo.test/',
        force: true,
    );

    expect($data->siteNames)->toBe(['Demo Site'])
        ->and($data->languageCodes)->toBe(['en', 'cy'])
        ->and($data->baseUrl)->toBe('https://demo.test')
        ->and($data->force)->toBeTrue();
});

it('provides public safe demo media urls', function (): void {
    $urls = ThemeDemoMedia::forTheme('commerce');

    expect($urls)->not->toBeEmpty();

    foreach ($urls as $url) {
        expect($url)
            ->toStartWith('https://')
            ->not->toContain('admin')
            ->not->toContain('signed')
            ->not->toContain('filament');
    }
});

it('resolves every first-party non-foundation theme to a deliberate media pool', function (): void {
    $defaultMedia = ThemeDemoMedia::groupedForTheme('default');
    $themeKeys = [
        'agency', 'awards', 'blog', 'brutalist', 'business', 'catalogue',
        'curated', 'directory', 'editorial', 'events', 'knowledge',
        'liquid-glass', 'magazine', 'minimalist', 'onepage', 'photography',
        'platform', 'portfolio', 'saas', 'showreel', 'submissions', 'bistro',
    ];

    foreach ($themeKeys as $themeKey) {
        expect(ThemeDemoMedia::groupedForTheme($themeKey))
            ->not->toBe($defaultMedia, "Theme [{$themeKey}] must not inherit Foundation's default media pool.");
    }
});

it('keeps Bistro preview media relevant to hospitality', function (): void {
    $media = ThemeDemoMedia::groupedForTheme('bistro');

    expect($media)->not->toBe(ThemeDemoMedia::groupedForTheme('default'));

    foreach ($media as $urls) {
        foreach ($urls as $url) {
            expect($url)
                ->not->toContain('photo-1497366754035-f200968a6e72')
                ->not->toContain('photo-1497215842964-222b430dc094')
                ->not->toContain('photo-1497366811353-6870744d04b2');
        }
    }
});

it('keeps local service proof media relevant to plumbing and electrical work', function (): void {
    $businessMedia = ThemeDemoMedia::groupedForTheme('business');

    expect($businessMedia['detail'])
        ->toHaveCount(2)
        ->and($businessMedia['detail'][0])->toContain('photo-1676210134188-4c05dd172f89')
        ->and($businessMedia['detail'][1])->toContain('photo-1758101755915-462eddc23f57')
        ->and($businessMedia['proof'])
        ->toHaveCount(3)
        ->and($businessMedia['proof'][0])->toContain('photo-1676210133055-eab6ef033ce3')
        ->and($businessMedia['proof'][1])->toContain('photo-1566417110090-6b15a06ec800');
});
