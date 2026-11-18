<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\Demo\ThemeDemoMedia;

it('lists each image once in a borrowing pool', function (): void {
    $keys = ThemeDemoMedia::distinctPool('portfolio');

    expect($keys)->toBe(array_values(array_unique($keys)))
        ->and(count($keys))->toBeGreaterThanOrEqual(18);
});

it('hands successive windows disjoint photographs', function (): void {
    $first = ThemeDemoMedia::window('onepage', 1, 3)['listing'];
    $second = ThemeDemoMedia::window('onepage', 4, 3)['listing'];

    expect($first)->toHaveCount(3)
        ->and(array_intersect($first, $second))->toBe([])
        ->and(ThemeDemoMedia::window('onepage', 1, 3)['hero'])->toBe($first);
});

it('keeps other themes on their own pool when windows borrow photography', function (): void {
    expect(ThemeDemoMedia::groupedForTheme('bistro'))->toBe(ThemeDemoMedia::groupedForTheme('bistro'))
        ->and(ThemeDemoMedia::distinctPool('bistro'))->toHaveCount(6);
});

it('serves every pool image from the site origin', function (): void {
    $themes = ['agency', 'bistro', 'brutalist', 'business', 'catalogue', 'default', 'directory', 'events', 'knowledge', 'liquid-glass', 'magazine', 'onepage', 'paperdesk', 'portfolio', 'saas', 'submissions'];

    foreach ($themes as $theme) {
        foreach (array_merge(ThemeDemoMedia::distinctPool($theme), ThemeDemoMedia::forTheme($theme)) as $url) {
            expect($url)->toStartWith('/vendor/capell-theme-foundation/demo-media/');
        }
    }
});
