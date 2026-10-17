<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\WidgetDataChart\Data\DataChartRenderData;
use Illuminate\Support\Facades\Blade;

require_once __DIR__ . '/../Support/DomQueries.php';

/*
 * `text-secondary` is a light palette colour (#0f172a by default) with no dark
 * counterpart, so content titles and list links rendered it on the dark page
 * surface at about 1:1 contrast. They now switch to Foundation's runtime text
 * tokens in dark mode, which `.dark:root` redefines for the dark surfaces.
 * Content typography also needs those colours through its RGB-channel tokens,
 * even on pages without the default-theme wrapper.
 */
it('gives content titles and list links a dark-mode text token', function (): void {
    $title = Blade::render('<x-capell::content title="Field notes" heading-tag="h1" heading-size="h1" />');
    $link = Blade::render('<x-capell::list.list-item url="/field-notes">Field notes</x-capell::list.list-item>');

    expect(domQueryCount($title, '//h1[contains(concat(" ", normalize-space(@class), " "), " text-secondary ") and contains(concat(" ", normalize-space(@class), " "), " dark:text-[var(--foundation-heading-fg)] ")]'))->toBe(1)
        ->and(domQueryCount($link, '//a[@href="/field-notes"][contains(concat(" ", normalize-space(@class), " "), " text-secondary ") and contains(concat(" ", normalize-space(@class), " "), " dark:text-[var(--foundation-body-fg)] ")]'))->toBe(1);
});

it('resolves the dark-mode text tokens to WCAG AA contrast on every dark Foundation surface', function (): void {
    $tokens = Blade::render('<x-capell::app.head.tokens />');

    preg_match('/\.dark:root\s*\{(?<declarations>[^}]*)\}/', $tokens, $match);
    $declarations = $match['declarations'] ?? '';

    preg_match_all('/(?<name>--[a-z-]+):\s*(?<value>[^;]+);/', $declarations, $pairs, PREG_SET_ORDER);
    $darkTokens = [];

    foreach ($pairs as $pair) {
        $darkTokens[$pair['name']] = trim($pair['value']);
    }

    $textColours = [];

    foreach (['--foundation-heading-fg', '--foundation-body-fg', '--foundation-muted-fg'] as $text) {
        expect($darkTokens)->toHaveKey($text);
        $textColours[$text] = darkFoundationRgb($darkTokens[$text], $darkTokens);
    }

    $typography = (string) file_get_contents(dirname(__DIR__, 4) . '/vendor/capell-app/frontend/resources/css/base/typography.css');
    $contentSelectors = [
        '--color-heading' => '.content-component :where(h1, h2, h3, h4, h5, h6)',
        'table headings' => '.content-component :where(th)',
        '--color-base' => '.content-component',
        '--color-muted' => '.content-component :where(blockquote)',
    ];

    foreach ($contentSelectors as $text => $selector) {
        $matched = preg_match('/' . preg_quote($selector, '/') . '\s*\{[^}]*?\bcolor:\s*(?<colour>[^;]+);/', $typography, $rule);

        if ($matched !== 1) {
            throw new RuntimeException(sprintf('Missing typography colour for %s.', $selector));
        }

        // Resolve the actual colour expression, including its slate fallback.
        $textColours[$text] = darkFoundationRgb($rule['colour'], $darkTokens);
    }

    expect($darkTokens)->toHaveKeys(['--foundation-page-bg', '--foundation-surface-bg', '--foundation-muted-bg', '--foundation-card-bg']);
    $darkSurfaces = array_filter(
        $darkTokens,
        static fn (string $name): bool => str_starts_with($name, '--foundation-') && str_ends_with($name, '-bg'),
        ARRAY_FILTER_USE_KEY,
    );

    foreach ($textColours as $text => $foreground) {
        foreach ($darkSurfaces as $surface => $background) {
            $ratio = darkFoundationContrast($foreground, darkFoundationRgb($background, $darkTokens));

            expect($ratio)->toBeGreaterThanOrEqual(4.5, sprintf('%s on %s is %.2f:1 in dark mode.', $text, $surface, $ratio));
        }
    }
});

/**
 * Resolve the hex, RGB-channel and variable forms used by Foundation typography.
 *
 * @param  array<string, string>  $tokens
 * @return list<int>
 */
function darkFoundationRgb(string $value, array $tokens): array
{
    $value = trim($value);

    if (preg_match('/^rgb\((.*)\)$/', $value, $rgb) === 1) {
        return darkFoundationRgb($rgb[1], $tokens);
    }

    if (preg_match('/^var\((--[a-z-]+)(?:,\s*(.*))?\)$/', $value, $variable) === 1) {
        return darkFoundationRgb($tokens[$variable[1]] ?? $variable[2] ?? '', $tokens);
    }

    if (preg_match('/^#[0-9a-fA-F]{3}$/', $value) === 1) {
        $value = '#' . implode('', array_map(static fn (string $channel): string => $channel . $channel, str_split(substr($value, 1))));
    }

    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1) {
        return array_map(static fn (string $channel): int => (int) hexdec($channel), str_split(substr($value, 1), 2));
    }

    expect($value)->toMatch('/^\d+[\s,]+\d+[\s,]+\d+$/');

    return array_map(intval(...), preg_split('/[\s,]+/', $value) ?: []);
}

it('pairs widget card text with a readable dark surface, including showcase cards', function (string $widget, string $selector): void {
    $tokens = darkFoundationWidgetTokens();
    $css = (string) file_get_contents(dirname(__DIR__, 3) . '/widget-' . $widget . '/resources/dist/widget.css');
    $card = darkFoundationCssDeclarations($css, $selector);
    $background = darkFoundationRgb($card['background'], $tokens);
    $typography = (string) file_get_contents(dirname(__DIR__, 4) . '/vendor/capell-app/frontend/resources/css/base/typography.css');
    $heading = darkFoundationCssDeclarations($typography, '.content-component :where(h1, h2, h3, h4, h5, h6)')['color'];

    foreach (['heading' => $heading, 'body' => $card['color']] as $text => $colour) {
        $ratio = darkFoundationContrast(darkFoundationRgb($colour, $tokens), $background);
        expect($ratio)->toBeGreaterThanOrEqual(4.5, sprintf('%s %s on its card is %.2f:1 in dark mode.', $widget, $text, $ratio));
    }
})->with([
    'Reference tone' => ['audio-playlist', '.capell-audio-playlist > ol > li'],
    'Open studio weekend' => ['countdown', '.capell-countdown'],
    'Workshop question and Results' => ['live-poll', '.capell-live-poll'],
    'Showcase reveal card' => ['content-reveal', '.capell-content-reveal article'],
    'Map fallback card' => ['location-map', '.capell-location-map__fallback'],
]);

it('keeps text links readable when widget cards switch to dark surfaces', function (string $widget, string $cardSelector, string $linkSelector): void {
    $tokens = darkFoundationWidgetTokens();
    $css = (string) file_get_contents(dirname(__DIR__, 3) . '/widget-' . $widget . '/resources/dist/widget.css');
    $card = darkFoundationCssDeclarations($css, $cardSelector);
    $typography = (string) file_get_contents(dirname(__DIR__, 4) . '/vendor/capell-app/frontend/resources/css/base/typography.css');
    $link = [...darkFoundationCssDeclarations($typography, '.content-component :where(a)'), ...darkFoundationCssDeclarations($css, $linkSelector)];
    $ratio = darkFoundationContrast(darkFoundationRgb($link['color'], $tokens), darkFoundationRgb($card['background'], $tokens));

    expect($ratio)->toBeGreaterThanOrEqual(4.5, sprintf('%s link on its dark card is %.2f:1.', $widget, $ratio));
})->with([
    'Countdown CTA' => ['countdown', '.capell-countdown', '.capell-countdown a'],
    'Map directions' => ['location-map', '.capell-location-map__fallback', '.capell-location-map__fallback a'],
]);

it('gives chart patterns non-text contrast on the page and revealed dark surfaces', function (): void {
    $tokens = darkFoundationWidgetTokens();
    $root = dirname(__DIR__, 3);
    $css = (string) file_get_contents($root . '/widget-data-chart/resources/dist/widget.css');
    $chart = darkFoundationCssDeclarations($css, '.capell-data-chart svg');
    $foreground = darkFoundationRgb($chart['color'], $tokens);
    $svg = DataChartRenderData::from([
        'title' => 'Attendance', 'type' => 'bar', 'series' => [
            ['label' => 'Visitors', 'points' => [['label' => 'April', 'value' => 84]]],
        ],
    ])->svg();

    // Pattern strokes and dots inherit the SVG colour, both standalone and in
    // the content-reveal interaction. Validate that actual render contract too.
    expect(domQueryCount($svg, '//pattern/path[@stroke="currentColor"]'))->toBe(3)
        ->and(domQueryCount($svg, '//pattern/circle[@fill="currentColor"]'))->toBe(1)
        ->and(domQueryCount($svg, '//rect[starts-with(@fill, "url(#pattern-")]'))->toBe(1);

    foreach (['--foundation-page-bg', '--foundation-surface-bg', '--foundation-card-bg'] as $surface) {
        $ratio = darkFoundationContrast($foreground, darkFoundationRgb($tokens[$surface], $tokens));
        expect($ratio)->toBeGreaterThanOrEqual(3.0, sprintf('Chart hatch on %s is %.2f:1 in dark mode.', $surface, $ratio));
    }
});

it('pairs widget action text and backgrounds with contrast on dark cards', function (string $widget, string $selector): void {
    $tokens = darkFoundationWidgetTokens();
    $css = (string) file_get_contents(dirname(__DIR__, 3) . '/widget-' . $widget . '/resources/dist/widget.css');
    $action = darkFoundationCssDeclarations($css, $selector);
    $background = darkFoundationRgb($action['background'], $tokens);
    $foreground = darkFoundationRgb($action['color'], $tokens);

    expect(darkFoundationContrast($foreground, $background))->toBeGreaterThanOrEqual(4.5, $widget . ' action text must meet WCAG AA.');
    foreach (['--foundation-page-bg', '--foundation-card-bg'] as $surface) {
        expect(darkFoundationContrast($background, darkFoundationRgb($tokens[$surface], $tokens)))
            ->toBeGreaterThanOrEqual(3.0, $widget . ' action must remain distinguishable on ' . $surface . '.');
    }
})->with([
    'Vote' => ['live-poll', '.capell-live-poll button'],
    'Reveal' => ['content-reveal', '.capell-content-reveal a'],
    'Hotspot dots' => ['hotspots', '.capell-hotspots summary'],
    'Play video' => ['youtube', '.capell-youtube__frame button'],
]);

/** @return array<string, string> */
function darkFoundationWidgetTokens(): array
{
    $html = Blade::render('<x-capell::app.head.tokens />');
    preg_match('/\.dark:root\s*\{(?<declarations>[^}]*)\}/', $html, $match);
    preg_match_all('/(?<name>--[a-z-]+):\s*(?<value>[^;]+);/', $match['declarations'] ?? '', $pairs, PREG_SET_ORDER);
    $tokens = (new BrandProfileData)->tokens();
    foreach ($pairs as $pair) {
        $tokens[$pair['name']] = trim($pair['value']);
    }

    return $tokens;
}

/** @return array<string, string> */
function darkFoundationCssDeclarations(string $css, string $selector): array
{
    $declarations = [];
    // Apply the shipped base rule followed by its dark descendant override.
    foreach ([$selector, '.dark ' . $selector] as $rule) {
        preg_match('/(?:^|\n)' . preg_quote($rule, '/') . '\s*\{(?<declarations>[^}]*)\}/', $css, $match);
        preg_match_all('/(?<name>[a-z-]+):\s*(?<value>[^;]+);/', $match['declarations'] ?? '', $pairs, PREG_SET_ORDER);
        foreach ($pairs as $pair) {
            $declarations[$pair['name']] = trim($pair['value']);
        }
    }

    return $declarations;
}

/**
 * @param  list<int>  $foreground
 * @param  list<int>  $background
 */
function darkFoundationContrast(array $foreground, array $background): float
{
    /** @param list<int> $rgb */
    $luminance = static function (array $rgb): float {
        $channels = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    };
    $lighter = max($luminance($foreground), $luminance($background));
    $darker = min($luminance($foreground), $luminance($background));

    return ($lighter + 0.05) / ($darker + 0.05);
}
