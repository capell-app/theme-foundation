<?php

declare(strict_types=1);

use Dom\HTMLDocument;

/*
 * A button, link or card whose destination is missing or unsafe used to render
 * as a link to "#", which looks clickable and goes nowhere. Buttons are now
 * left out, and links and cards keep their words as plain text.
 */
/** @param array<string, mixed> $section */
function foundationSectionWithoutDestinations(string $view, array $section): string
{
    $data = new class($section)
    {
        /** @param array<string, mixed> $values */
        public function __construct(private readonly array $values) {}

        public function __get(string $name): mixed
        {
            return $this->values[$name] ?? null;
        }

        public function __isset(string $name): bool
        {
            return isset($this->values[$name]);
        }
    };

    /** @var view-string $viewName */
    $viewName = sprintf('capell-theme-foundation::theme.sections.%s', $view);

    return view($viewName, ['section' => $data])->render();
}

it('renders no button for an action without a safe destination', function (string $view): void {
    $html = foundationSectionWithoutDestinations($view, [
        'heading' => 'Owner headline',
        'summary' => 'Owner summary',
        'actions' => [
            ['label' => 'Nowhere to go'],
            ['label' => 'Hostile', 'url' => 'javascript:alert(1)'],
            ['label' => 'Read the guide', 'url' => '/guide'],
        ],
    ]);

    expect($html)->toContain('Owner headline', 'Read the guide', 'href="/guide"')
        ->not->toContain('Nowhere to go')
        ->not->toContain('Hostile')
        ->not->toContain('href="#"')
        ->not->toContain('javascript:');
})->with(['hero', 'hero--split', 'hero--stacked', 'hero--full-bleed', 'cta', 'cta--band', 'cta--card', 'cta--inline']);

it('keeps a footer or navigation label as text when it has no destination', function (string $view, array $section): void {
    $html = foundationSectionWithoutDestinations($view, $section);

    $linkedLabels = [];

    foreach (HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('a') as $anchor) {
        $linkedLabels[] = trim((string) $anchor->textContent);
    }

    expect($html)->toContain('Plain label')
        ->not->toContain('href="#"')
        ->and($linkedLabels)->toContain('Linked')
        ->not->toContain('Plain label');
})->with([
    'footer' => ['footer', ['brandName' => 'Owner site', 'columns' => [['heading' => 'Explore', 'links' => [['label' => 'Plain label'], ['label' => 'Linked', 'url' => '/linked']]]]]],
    'navigation' => ['navigation', ['brandName' => 'Owner site', 'items' => [['label' => 'Plain label'], ['label' => 'Linked', 'url' => '/linked']]]],
]);

it('keeps a listing entry readable when it has no destination', function (string $variant): void {
    $html = foundationSectionWithoutDestinations('content-listing' . $variant, [
        'heading' => 'Explore',
        'summary' => '',
        'items' => [
            ['title' => 'Entry without a page', 'summary' => 'Still readable'],
            ['title' => 'Linked entry', 'summary' => 'Goes somewhere', 'url' => '/linked'],
        ],
    ]);

    expect($html)->toContain('Entry without a page', 'Linked entry', 'href="/linked"')
        ->not->toContain('href="#"');
})->with(['--rows', '--grid', '--masonry-safe']);
