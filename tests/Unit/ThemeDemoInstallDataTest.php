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
