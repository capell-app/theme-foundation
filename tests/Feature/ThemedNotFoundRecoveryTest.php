<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;

require_once dirname(__DIR__, 4) . '/tests/Packages/Support/ThemeLayoutNativeSupport.php';

afterEach(function (): void {
    CapellCore::clearPackages();
});

// A visitor on a flagship theme's 404 saw the recovery suggestions after the
// theme's footer, and a second "Page not found" heading from the demo prose
// under the hero. The suggestions belong in main content before the footer,
// and the page carries one heading.
it('renders one heading and the recovery suggestions before the footer', function (string $themeKey): void {
    [$pageUrl] = layoutNativeThemeCreatePage($themeKey, 'Recovery layout', 'not-found');
    $html = (string) $this->get($pageUrl->full_url)->assertNotFound()->getContent();
    expect($html)->not->toBeEmpty();
    assert($html !== '');

    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    $repeatedHeadings = $xpath->query('//main[@id="main"]//*[self::h2 or self::h3][contains(translate(normalize-space(.), "PAGE NOT FOUND", "page not found"), "page not found")]');

    expect($xpath->query('//h1'))->toHaveCount(1, $themeKey . ' renders more than one page heading')
        ->and($repeatedHeadings)->toHaveCount(0, $themeKey . ' repeats the page heading in the page content')
        ->and($xpath->query('//main[@id="main"]//*[contains(concat(" ", @class, " "), " content-component ")]//h2'))->toHaveCount(0, $themeKey . ' adds a second heading in the page content')
        ->and($xpath->query('//main[@id="main"]//section[@id="capell-smart-404"]'))->toHaveCount(1, $themeKey . ' renders suggestions outside main content')
        ->and($xpath->query('//section[@id="capell-smart-404"]/preceding::footer'))->toHaveCount(0, $themeKey . ' renders suggestions after the footer');
})->with(['default', 'platform', 'business', 'editorial', 'portfolio']);
