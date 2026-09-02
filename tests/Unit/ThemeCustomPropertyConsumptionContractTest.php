<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\BrandProfileData;

/** @return list<string> */
function themeContractExtractCustomProperties(string $source): array
{
    $source = (string) preg_replace('/\/\*.*?\*\//s', '', $source);

    preg_match_all('/--theme-(?:[a-z0-9]+(?:-[a-z0-9]+)*)/i', $source, $matches);

    $tokens = array_map(strtolower(...), $matches[0]);

    sort($tokens);

    return array_values(array_unique($tokens));
}

function themeContractFoundationStyleSheets(): string
{
    $packagesRoot = dirname(__DIR__, 3);
    $files = array_merge(
        glob($packagesRoot . '/theme-foundation/resources/css/**/*.css') ?: [],
        glob($packagesRoot . '/theme-foundation/resources/css/*.css') ?: [],
        glob($packagesRoot . '/theme-foundation/resources/views/**/*.blade.php') ?: [],
        glob($packagesRoot . '/theme-foundation/resources/views/*.blade.php') ?: [],
    );

    $contents = '';

    foreach ($files as $file) {
        $css = file_get_contents($file);

        if (is_string($css)) {
            $contents .= "\n" . $css;
        }
    }

    return $contents;
}

/** @return array<string, string> */
function themeContractThemeSheets(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $stylesheetFiles = glob($packagesRoot . '/theme-*/resources/css/theme-*.css') ?: [];

    sort($stylesheetFiles);

    $stylesheets = [];

    foreach ($stylesheetFiles as $stylesheetFile) {
        $packageDirectory = basename(dirname($stylesheetFile, 3));
        $contents = file_get_contents($stylesheetFile);

        if (is_string($contents)) {
            $stylesheets[$packageDirectory] = $contents;
        }
    }

    return $stylesheets;
}

/**
 * Return the source of the balanced `[...]` array passed as the named argument `$argument`.
 *
 * A non-greedy regex cannot be used here: it stops at the first `]`, which is the closing
 * bracket of the FIRST token's nested option list, silently hiding every later token.
 */
function themeContractExtractNamedArrayArgument(string $source, string $argument): ?string
{
    if (! preg_match('/\b' . preg_quote($argument, '/') . '\s*:\s*\[/', $source, $match, PREG_OFFSET_CAPTURE)) {
        return null;
    }

    $start = $match[0][1] + strlen($match[0][0]);
    $depth = 1;
    $length = strlen($source);

    for ($offset = $start; $offset < $length; $offset++) {
        $character = $source[$offset];

        if ($character === '[') {
            $depth++;
        } elseif ($character === ']') {
            $depth--;

            if ($depth === 0) {
                return substr($source, $start, $offset - $start);
            }
        }
    }

    return null;
}

/**
 * Keys declared at the top level of an array literal, ignoring keys of nested arrays
 * (so a token's own `'options' => [...]` is not mistaken for another token).
 *
 * @return list<string>
 */
function themeContractTopLevelArrayKeys(string $arrayBody): array
{
    preg_match_all('/[\'\"]([a-zA-Z0-9_]+)[\'\"]\s*=>\s*\[/', $arrayBody, $matches, PREG_OFFSET_CAPTURE);

    $keys = [];

    foreach ($matches[1] as $index => $capture) {
        $prefix = substr($arrayBody, 0, (int) $matches[0][$index][1]);

        if (substr_count($prefix, '[') === substr_count($prefix, ']')) {
            $keys[] = (string) $capture[0];
        }
    }

    return $keys;
}

/** @return list<string> */
function themeContractThemeEditorExtraTokens(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    // Not `*ThemeServiceProvider.php`: theme-bistro names its provider `ThemeBistroServiceProvider`,
    // so the narrower glob skipped it entirely and lost its declared editor tokens.
    $providerFiles = glob($packagesRoot . '/theme-*/src/*ServiceProvider.php') ?: [];

    $tokens = [];

    foreach ($providerFiles as $providerFile) {
        $contents = file_get_contents($providerFile);

        if (! is_string($contents)) {
            continue;
        }

        $tokenNames = [];

        // Idiom 1: StandardThemeEditorSchema::withExtraTokens(tokens: [...]).
        if (str_contains($contents, 'withExtraTokens')) {
            $tokenBlock = themeContractExtractNamedArrayArgument($contents, 'tokens');

            if ($tokenBlock !== null) {
                $tokenNames = themeContractTopLevelArrayKeys($tokenBlock);
            }
        }

        // Idiom 2: mutating the resolved schema directly, e.g. $schema['tokens']['deskScatter'] = [...].
        preg_match_all('/\[\s*[\'\"]tokens[\'\"]\s*\]\s*\[\s*[\'\"]([a-zA-Z0-9_]+)[\'\"]\s*\]\s*=/', $contents, $assigned);
        $tokenNames = [...$tokenNames, ...$assigned[1]];

        foreach ($tokenNames as $tokenName) {
            $kebab = strtolower((string) preg_replace('/(?<!^)([A-Z])/', '-$1', $tokenName));
            $tokens[] = "--theme-{$kebab}";
        }
    }

    sort($tokens);

    return array_values(array_unique($tokens));
}

/** @return array<string, string> */
function themeContractThemeAliasTokens(string $source): array
{
    preg_match_all(
        '/(--theme-[a-z0-9]+(?:-[a-z0-9]+)*)\s*:\s*var\(\\s*(--theme-[a-z0-9]+(?:-[a-z0-9]+)*)/i',
        $source,
        $matches,
        PREG_SET_ORDER,
    );

    $aliases = [];

    foreach ($matches as $match) {
        $alias = strtolower($match[1]);
        $target = strtolower($match[2]);

        if ($alias === $target) {
            continue;
        }

        $aliases[$alias] = $target;
    }

    return $aliases;
}

it('only consumes emitted theme custom properties', function (): void {
    $themeContractEmitted = array_unique([
        ...array_keys((new BrandProfileData)->tokens()),
        ...themeContractExtractCustomProperties(themeContractFoundationStyleSheets()),
        ...themeContractThemeEditorExtraTokens(),
    ]);

    sort($themeContractEmitted);

    $themeStylesheets = themeContractThemeSheets();

    expect($themeStylesheets)->not->toBeEmpty();
    // Census of shipped theme stylesheets. This literal is deliberate: it fails loudly when a
    // theme stylesheet silently disappears (a deleted/renamed package, or a moved CSS path).
    // Update the number only when a theme is intentionally added or removed — never delete
    // this assertion to make the suite green.
    // 29 -> 28 on 2026-08-31: PR #782 retired the Photography theme, consolidating it into
    // Brutalist as the `magazine-masthead` preset. One package, one stylesheet, intentionally
    // removed — the census follows the shipped fleet, which is now 28 theme packages.
    expect($themeStylesheets)->toHaveLength(28);

    foreach ($themeStylesheets as $themeDirectory => $themeStylesheet) {
        $consumedTokens = themeContractExtractCustomProperties($themeStylesheet);
        $themeAliasTokens = themeContractThemeAliasTokens($themeStylesheet);

        $unresolvedAliasTargets = [];
        foreach ($themeAliasTokens as $alias => $target) {
            if (! in_array($target, $themeContractEmitted, true) && ! array_key_exists($target, $themeAliasTokens)) {
                $unresolvedAliasTargets[] = $alias;
            }
        }

        expect($unresolvedAliasTargets)->toBe(
            [],
            "{$themeDirectory} defines a local --theme-* alias target that is not emitted by Foundation or another local alias: " . implode(', ', $unresolvedAliasTargets),
        );

        $knownTokens = array_unique([
            ...$themeContractEmitted,
            ...array_keys($themeAliasTokens),
        ]);

        $unknownTokens = array_values(array_diff($consumedTokens, $knownTokens));

        expect($unknownTokens)->toBe(
            [],
            "{$themeDirectory} consumes a --theme-* custom property that Foundation does not emit: " . implode(', ', $unknownTokens),
        );
    }
});
