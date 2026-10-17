<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Theme;
use Capell\Frontend\Support\State\FrontendState;
use Illuminate\Support\HtmlString;

require_once __DIR__ . '/../Support/DomQueries.php';

test('renders the shell direction for resolved languages and the locale fallback', function (?string $code, string $locale, string $direction): void {
    app()->setLocale($locale);
    resolve(FrontendState::class)->reset();
    if ($code !== null) {
        resolve(FrontendState::class)->withLanguage(new Language(['code' => $code]));
    }
    $theme = new Theme(['key' => 'default']);
    resolve(FrontendState::class)->withTheme($theme);
    $page = Page::factory()->withTranslations()->create();
    resolve(FrontendState::class)->withPage($page)->withSite($page->site)->withLayout($page->layout);
    if ($code === null) {
        // The head requires a language model; isolate the shell's locale fallback from that boundary.
        view()->prependNamespace('capell', __DIR__ . '/../Fixtures/direction');
        view()->getFinder()->flush();
    }
    $html = view('capell-theme-foundation::app', [
        'layout' => new Layout(['key' => 'default']),
        'theme' => $theme,
        'slot' => new HtmlString('<p>Public page</p>'),
    ])->render();

    expect(domQueryCount($html, '//html[@dir="' . $direction . '"]'))->toBe(1)
        ->and($html)->toContain('Public page');
})->with([
    'Arabic' => ['ar', 'en', 'rtl'],
    'French' => ['fr', 'en', 'ltr'],
    'Arabic locale fallback' => [null, 'ar', 'rtl'],
    'French locale fallback' => [null, 'fr', 'ltr'],
]);
