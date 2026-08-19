<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\BrandProfileData;

/** @return list<string> */
function themeContractExtractCustomProperties(string $source): array
{
    $source = (string) preg_replace('/\/\*.*?\*\//s', '', $source);

    preg_match_all('/--theme-(?:[a-z0-9]+(?:-[a-z0-9]+)*)/i', $source, $matches);

    $tokens = array_map('strtolower', $matches[0]);

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

/** @return list<string> */
function themeContractThemeEditorExtraTokens(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $providerFiles = glob($packagesRoot . '/theme-*/src/*ThemeServiceProvider.php') ?: [];

    $tokens = [];

    foreach ($providerFiles as $providerFile) {
        $contents = file_get_contents($providerFile);

        if (! is_string($contents) || ! str_contains($contents, 'withExtraTokens')) {
            continue;
        }

        if (! preg_match('/tokens\s*:\s*\[(.*?)\]/s', $contents, $tokenMatch)) {
            continue;
        }

        preg_match_all('/[\'\"]([a-zA-Z0-9_]+)[\'\"]\s*=>\s*\[/m', $tokenMatch[1], $tokenKeys);

        foreach ($tokenKeys[1] as $tokenName) {
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
    expect($themeStylesheets)->toHaveLength(30);

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
