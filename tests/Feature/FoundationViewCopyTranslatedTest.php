<?php

declare(strict_types=1);

/**
 * Returns the English-looking text left in a Blade view once styles, scripts,
 * comments, echoes, directives, code samples and tags are removed.
 */
function foundationUntranslatedCopy(string $blade): string
{
    $tag = '<\/?[A-Za-z][^"\'>]*(?:(?:"[^"]*"|\'[^\']*\')[^"\'>]*)*>';

    $patterns = [
        '/<style\b.*?<\/style>/is',
        '/<script\b.*?<\/script>/is',
        '/<pre\b.*?<\/pre>/is',
        '/\{\{--.*?--\}\}/s',
        '/\{!!.*?!!\}/s',
        '/\{\{.*?\}\}/s',
        '/@props\(\[.*?\]\)/s',
        '/@php\b.*?@endphp/s',
        '/^[ \t]*@\w+(?:\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\))?.*$/m',
        '/' . $tag . '/s',
    ];

    return trim((string) preg_replace($patterns, '', $blade));
}

it('detects bare English copy in a view', function (): void {
    expect(foundationUntranslatedCopy('<p>Hello there</p>'))->toMatch('/[A-Za-z]{2,}/');
});

it('ignores translated copy, echoes, directives and styles', function (): void {
    $blade = <<<'BLADE'
    <style>.a { color: red; }</style>
    @foreach ($items as $item)
        <p class="x">{{ __('capell-theme-foundation::demo.example') }}</p>
    @endforeach
    BLADE;

    expect(foundationUntranslatedCopy($blade))->not->toMatch('/[A-Za-z]{2,}/');
});

it('keeps hard-coded English out of the public view', function (string $view): void {
    $blade = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/' . $view);

    expect($blade)->not->toBeFalse()
        ->and(foundationUntranslatedCopy((string) $blade))->not->toMatch('/[A-Za-z]{2,}/');
})->with([
    'contact page' => 'demo/contact-page.blade.php',
    'cta section' => 'widget/modern/cta-section.blade.php',
    'kitchen sink reference' => 'widget/kitchen-sink/reference.blade.php',
]);
