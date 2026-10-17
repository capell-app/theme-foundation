<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/** @return array<string, string> */
function captureFoundationTokens(bool $dark): array
{
    $html = strip_tags(Blade::render('<x-capell::app.head.tokens />'));
    $html = (string) preg_replace('/^\h+/m', '', $html);

    $tokens = captureCssDeclarations($html, ':root');

    return $dark ? [...$tokens, ...captureCssDeclarations($html, '.dark:root')] : $tokens;
}

/** @return array<string, string> */
function captureCssDeclarations(string $css, string $selector): array
{
    $css = (string) preg_replace('~/\*.*?\*/~s', '', $css);
    $css = (string) preg_replace('/@(?:media|container|supports)[^{]*(\{(?:[^{}]++|(?1))*\})/s', '', $css);
    preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);
    $declarations = [];
    foreach ($rules as $rule) {
        if (! in_array($selector, array_map(trim(...), explode(',', trim($rule[1]))), true)) {
            continue;
        }

        preg_match_all('/([a-z-]+):\s*([^;]+);/', $rule[2], $pairs, PREG_SET_ORDER);
        foreach ($pairs as $pair) {
            $declarations[$pair[1]] = trim($pair[2]);
        }
    }

    return $declarations;
}

/**
 * @param  array<string, string>  $tokens
 * @return list<int>
 */
function captureTokenRgb(string $value, array $tokens): array
{
    $value = (string) preg_replace('/\s+/', ' ', trim($value));
    $value = (string) preg_replace(['/\(\s+/', '/\s+\)/'], ['(', ')'], $value);
    if ($value === 'white') {
        return [255, 255, 255];
    }

    if (preg_match('/^var\((--[a-z-]+)(?:,\s*(.*))?\)$/s', $value, $variable) === 1) {
        return captureTokenRgb($tokens[$variable[1]] ?? $variable[2] ?? '', $tokens);
    }

    if (preg_match('/^rgb\((.*)\)$/s', $value, $rgb) === 1) {
        return captureTokenRgb($rgb[1], $tokens);
    }

    if (preg_match('/^color-mix\(in srgb, (var\(--[a-z-]+(?:, [^)]*)?\)|#[0-9a-fA-F]{6}) (\d+)%, (.*?)(?: \d+%)?\)$/s', $value, $mix) === 1) {
        $first = captureTokenRgb($mix[1], $tokens);
        $second = captureTokenRgb($mix[3], $tokens);
        $weight = (int) $mix[2] / 100;

        return array_map(static fn (int $a, int $b): int => (int) round($a * $weight + $b * (1 - $weight)), $first, $second);
    }

    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1) {
        return array_map(static fn (string $channel): int => (int) hexdec($channel), str_split(substr($value, 1), 2));
    }

    expect($value)->toMatch('/^\d+[\s,]+\d+[\s,]+\d+$/');

    return array_map(intval(...), preg_split('/[\s,]+/', $value) ?: []);
}

/**
 * @param  list<int>  $foreground
 * @param  list<int>  $background
 */
function captureContrast(array $foreground, array $background): float
{
    /** @param list<int> $rgb */
    $luminance = static function (array $rgb): float {
        $channels = array_map(static function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    };

    return (max($luminance($foreground), $luminance($background)) + 0.05)
        / (min($luminance($foreground), $luminance($background)) + 0.05);
}
