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
            ->toStartWith('/vendor/capell-theme-foundation/demo-media/')
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

    expect(array_intersect(ThemeDemoMedia::forTheme('bistro'), ThemeDemoMedia::forTheme('default')))->toBe([]);

    foreach ($media as $urls) {
        foreach ($urls as $url) {
            expect($url)->toStartWith('/vendor/capell-theme-foundation/demo-media/');
        }
    }
});

it('keeps local service proof media relevant to plumbing and electrical work', function (): void {
    $businessMedia = ThemeDemoMedia::groupedForTheme('business');

    expect($businessMedia['detail'])
        ->toHaveCount(2)
        ->and($businessMedia['detail'][0])->toBe(themeDemoInstallMediaUrl('local-services/detail-1.webp'))
        ->and($businessMedia['detail'][1])->toBe(themeDemoInstallMediaUrl('local-services/detail-2.webp'))
        ->and($businessMedia['proof'])
        ->toHaveCount(3)
        ->and($businessMedia['proof'][0])->toBe(themeDemoInstallMediaUrl('local-services/proof-1.webp'))
        ->and($businessMedia['proof'][1])->toBe(themeDemoInstallMediaUrl('local-services/proof-2.webp'));
});

it('keeps Paperdesk reference photographs separate from the default office pool', function (): void {
    $media = ThemeDemoMedia::groupedForTheme('paperdesk');
    $defaultUrls = ThemeDemoMedia::forTheme('default');

    expect($media['listing'])->toHaveCount(3);
    expect($media['detail'])->toHaveCount(5);

    foreach ($media as $urls) {
        expect($urls)->not->toBeEmpty();

        foreach ($urls as $url) {
            expect($url)->toStartWith('/vendor/capell-theme-foundation/demo-media/');
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
        ['paperdesk/detail-1.webp', 'alphabet specimen'],
        ['paperdesk/detail-2.webp', 'book pages'],
        ['paperdesk/detail-3.webp', 'book bindings'],
        ['paperdesk/detail-4.webp', 'postcard'],
        ['paperdesk/detail-5.webp', 'bookbinding press'],
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
        expect($item['imageUrl'])->toBe(themeDemoInstallMediaUrl($photo));
        expect($item['imageAlt'])->toContain('Reference photograph', $subject);
    }

    foreach (array_slice($deskItems, 0, 3) as $item) {
        $this->assertIsArray($item);
        expect($item['imageAlt'])->toContain('Reference photograph');
        expect($item['summary'])->toContain('Fictional', 'not');
    }
});

function themeDemoInstallMediaUrl(string $path): string
{
    return '/vendor/capell-theme-foundation/demo-media/' . $path;
}
