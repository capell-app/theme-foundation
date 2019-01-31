<?php

declare(strict_types=1);

use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Support\Demo\ThemeDemoMedia;
use Capell\ThemePaperdesk\Support\Demo\PaperdeskDemoContent;

it('normalizes theme demo install data', function (): void {
    $data = new ThemeDemoInstallData(
        siteNames: ['Demo Site', ''],
        languageCodes: ['en', 'cy'],
        baseUrl: 'https://demo.test/',
        force: true,
        profile: ' after-hours ',
    );

    expect($data->siteNames)->toBe(['Demo Site'])
        ->and($data->languageCodes)->toBe(['en', 'cy'])
        ->and($data->baseUrl)->toBe('https://demo.test')
        ->and($data->force)->toBeTrue();

    expect($data->profile)->toBe('after-hours');
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
        'liquid-glass', 'magazine', 'minimalist', 'onepage',
        'platform', 'portfolio', 'saas', 'showreel', 'submissions', 'bistro', 'paperdesk',
    ];

    foreach ($themeKeys as $themeKey) {
        expect(ThemeDemoMedia::groupedForTheme($themeKey))
            ->not->toBe($defaultMedia, "Theme [{$themeKey}] must not inherit Foundation's default media pool.");
    }
});

it('does not retain a media alias for the retired Photography theme', function (): void {
    expect(ThemeDemoMedia::groupedForTheme('photography'))
        ->toBe(ThemeDemoMedia::groupedForTheme('default'));
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

it('keeps Paperdesk reference photographs separate from the default office pool', function (): void {
    $media = ThemeDemoMedia::groupedForTheme('paperdesk');
    $defaultUrls = ThemeDemoMedia::forTheme('default');

    expect($media['listing'])->toHaveCount(3);
    expect($media['detail'])->toHaveCount(5);

    foreach ($media as $urls) {
        expect($urls)->not->toBeEmpty();

        foreach ($urls as $url) {
            expect($url)->toStartWith('https://images.unsplash.com/photo-');
            expect($defaultUrls)->not->toContain($url);
        }
    }
});

it('describes the selected Paperdesk photographs without claiming they show its commissions', function (): void {
    $media = ThemeDemoMedia::groupedForTheme('paperdesk');
    $content = new PaperdeskDemoContent;
    $homepage = $content->sections('homepage', $media);
    $detail = $content->sections('detail', $media);
    $subjects = [
        ['photo-1532153389802-6e84e367ed5e', 'alphabet specimen'],
        ['photo-1530951517437-1b43a7349b10', 'book pages'],
        ['photo-1595123336219-5eedd543bc4a', 'book bindings'],
        ['photo-1565893181327-cd3a5d5752b5', 'postcard'],
        ['photo-1574935905666-e5754382b0b5', 'bookbinding press'],
    ];

    expect($homepage[0]['mediaAlt'])->toContain('reference photograph', 'bookbinding press');
    $detailItems = $detail[0]['items'];
    $deskItems = $homepage[1]['items'];
    $this->assertIsArray($detailItems);
    $this->assertIsArray($deskItems);
    expect($detailItems)->toHaveCount(5);

    foreach ($subjects as $index => [$photo, $subject]) {
        $item = $detailItems[$index];
        $this->assertIsArray($item);
        expect($item['imageUrl'])->toContain($photo);
        expect($item['imageAlt'])->toContain('Reference photograph', $subject);
    }

    foreach (array_slice($deskItems, 0, 3) as $item) {
        $this->assertIsArray($item);
        expect($item['imageAlt'])->toContain('Reference photograph');
        expect($item['summary'])->toContain('Fictional', 'not');
    }
});
