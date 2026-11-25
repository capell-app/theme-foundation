<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\Frontend\Actions\GetLayoutContainerWidthAction;

use function Pest\Laravel\get;

require_once __DIR__ . '/../../../../tests/Packages/Support/ThemeLayoutNativeSupport.php';

// A layout without containers renders its page content through the main
// landmark's fallback. That fallback sat in a bare padded column, so on a wide
// screen the page title started well left of the header and footer, which use
// the theme container. The content must share that container.

it('renders layout-free page content inside the same container as the header and footer', function (): void {
    $registry = resolve(ThemeRegistry::class);
    $registry->reset();

    [$pageUrl] = layoutNativeThemeCreatePage('default', 'Fallback Container Regression');

    $page = Page::query()->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    Layout::query()->findOrFail($page->layout_id)->forceFill(['containers' => null])->save();

    $response = get($pageUrl->full_url);
    $response->assertOk();

    $html = $response->getContent();

    if (! is_string($html) || $html === '') {
        throw new RuntimeException('Expected the layout-free page to render HTML.');
    }

    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    $wrappers = $xpath->query('//main//div[contains(concat(" ", normalize-space(@class), " "), " capell-layout-main__content ")]');
    $content = $wrappers instanceof DOMNodeList ? $wrappers->item(0) : null;

    throw_unless($content instanceof DOMElement, RuntimeException::class, 'The main landmark must wrap layout-free page content.');

    expect($wrappers)->toHaveCount(1)
        ->and($content->getAttribute('class'))->toContain(GetLayoutContainerWidthAction::run()->getContainerClass())
        ->and($xpath->query('.//h1', $content))->toHaveCount(1);

    $registry->reset();
    CapellCore::clearPackages();
});
