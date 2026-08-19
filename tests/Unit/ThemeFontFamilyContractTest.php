<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\BrandProfileData;

/** @return array<string, string> */
function themeFontFamilyContractStylesheets(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $files = array_merge(
        glob($packagesRoot . '/theme-*/resources/css/theme-*.css') ?: [],
        glob($packagesRoot . '/theme-foundation/resources/css/theme/**/*.css') ?: [],
    );

    sort($files);

    $stylesheets = [];

    foreach ($files as $file) {
        $source = file_get_contents($file);

        if (is_string($source)) {
            $stylesheets[$file] = (string) preg_replace('/\/\*.*?\*\//s', '', $source);
        }
    }

    return $stylesheets;
}

/** @return array<string, string> */
function themeFontFamilyContractCustomProperties(string $source): array
{
    preg_match_all(
        '/(?<![a-z0-9_-])(--[a-z0-9-]+)\s*:\s*([^;{}]+);/is',
        $source,
        $matches,
        PREG_SET_ORDER,
    );

    $properties = [];

    foreach ($matches as $match) {
        $properties[strtolower($match[1])] = trim($match[2]);
    }

    return $properties;
}

/** @return list<string> */
function themeFontFamilyContractSplitTopLevel(string $value): array
{
    $parts = [];
    $part = '';
    $depth = 0;
    $quote = null;

    for ($index = 0, $length = strlen($value); $index < $length; $index++) {
        $character = $value[$index];

        if ($quote !== null) {
            $part .= $character;

            if ($character === $quote && ($index === 0 || $value[$index - 1] !== '\\')) {
                $quote = null;
            }

            continue;
        }

        if ($character === "'" || $character === '"') {
            $quote = $character;
            $part .= $character;
        } elseif ($character === '(') {
            $depth++;
            $part .= $character;
        } elseif ($character === ')') {
            $depth--;
            $part .= $character;
        } elseif ($character === ',' && $depth === 0) {
            $parts[] = trim($part);
            $part = '';
        } else {
            $part .= $character;
        }
    }

    $parts[] = trim($part);

    return $parts;
}

/** @return array{content: string, end: int}|null */
function themeFontFamilyContractVarContents(string $value, int $start): ?array
{
    $depth = 1;
    $quote = null;

    for ($index = $start + 4, $length = strlen($value); $index < $length; $index++) {
        $character = $value[$index];

        if ($quote !== null) {
            if ($character === $quote && $value[$index - 1] !== '\\') {
                $quote = null;
            }

            continue;
        }

        if ($character === "'" || $character === '"') {
            $quote = $character;
        } elseif ($character === '(') {
            $depth++;
        } elseif ($character === ')') {
            $depth--;

            if ($depth === 0) {
                return [
                    'content' => substr($value, $start + 4, $index - $start - 4),
                    'end' => $index,
                ];
            }
        }
    }

    return null;
}

/**
 * @param  array<string, string>  $localProperties
 * @param  array<string, string>  $emittedProperties
 * @param  array<string, true>  $seen
 */
function themeFontFamilyContractExpand(
    string $value,
    array $localProperties,
    array $emittedProperties,
    array $seen = [],
): string {
    while (($start = stripos($value, 'var(')) !== false) {
        $var = themeFontFamilyContractVarContents($value, $start);

        if ($var === null) {
            return $value;
        }

        $parts = themeFontFamilyContractSplitTopLevel($var['content']);
        $property = strtolower(trim((string) array_shift($parts)));

        if (array_key_exists($property, $seen)) {
            $replacement = '__recursive_var__';
        } elseif (array_key_exists($property, $localProperties)) {
            $replacement = themeFontFamilyContractExpand(
                $localProperties[$property],
                $localProperties,
                $emittedProperties,
                [...$seen, $property => true],
            );
        } elseif (array_key_exists($property, $emittedProperties)) {
            $replacement = $emittedProperties[$property];
        } elseif ($parts !== []) {
            $replacement = themeFontFamilyContractExpand(
                implode(',', $parts),
                $localProperties,
                $emittedProperties,
                $seen,
            );
        } else {
            $replacement = '__unresolved_var__';
        }

        $value = substr($value, 0, $start) . $replacement . substr($value, $var['end'] + 1);
    }

    return $value;
}

/** @return list<string> */
function themeFontFamilyContractVarFallbacks(string $value): array
{
    $fallbacks = [];
    $offset = 0;

    while (($relativeStart = stripos(substr($value, $offset), 'var(')) !== false) {
        $start = $offset + $relativeStart;
        $var = themeFontFamilyContractVarContents($value, $start);

        if ($var === null) {
            break;
        }

        $parts = themeFontFamilyContractSplitTopLevel($var['content']);

        if (count($parts) > 1) {
            $fallback = implode(',', array_slice($parts, 1));
            $fallbacks[] = $fallback;
            $fallbacks = [...$fallbacks, ...themeFontFamilyContractVarFallbacks($fallback)];
        }

        $offset = $var['end'] + 1;
    }

    return $fallbacks;
}

function themeFontFamilyContractTerminatesInGeneric(string $value): bool
{
    $genericFamilies = [
        'cursive',
        'emoji',
        'fangsong',
        'fantasy',
        'math',
        'monospace',
        'sans-serif',
        'serif',
        'system-ui',
        'ui-monospace',
        'ui-rounded',
        'ui-sans-serif',
        'ui-serif',
    ];

    $families = themeFontFamilyContractSplitTopLevel($value);
    $lastFamily = strtolower(trim((string) end($families)));

    return in_array($lastFamily, $genericFamilies, true);
}

/** @return list<string> */
function themeFontFamilyContractKnownBundledFamilies(): array
{
    return [
        'archivo',
        'bricolage grotesque',
        'fraunces',
        'ibm plex mono',
        'ibm plex sans',
        'inter',
        'jetbrains mono',
        'manrope',
        'newsreader',
        'playfair display',
        'sora',
        'source sans 3',
        'source serif 4',
        'space grotesk',
    ];
}

/** @return list<string> */
function themeFontFamilyContractDeclarations(string $source): array
{
    $source = (string) preg_replace('/@font-face\s*\{.*?\}/is', '', $source);

    preg_match_all('/font-family\s*:\s*([^;{}]+);/i', $source, $matches);

    return array_map('trim', $matches[1]);
}

it('requires every font-family fallback chain to terminate in a generic family', function (): void {
    $emittedProperties = (new BrandProfileData)->tokens();

    foreach (themeFontFamilyContractStylesheets() as $file => $stylesheet) {
        $localProperties = themeFontFamilyContractCustomProperties($stylesheet);
        $fontValues = themeFontFamilyContractDeclarations($stylesheet);

        foreach ($localProperties as $property => $value) {
            if (! str_contains($property, 'font')) {
                continue;
            }

            $fontValues[] = $value;
        }

        foreach ($fontValues as $fontValue) {
            $expanded = themeFontFamilyContractExpand($fontValue, $localProperties, $emittedProperties);

            if (in_array(strtolower(trim($expanded)), ['inherit', 'initial', 'revert', 'revert-layer', 'unset'], true)) {
                continue;
            }

            expect(themeFontFamilyContractTerminatesInGeneric($expanded))->toBeTrue(
                basename($file) . " has a font-family chain without a generic terminator: {$fontValue}",
            );

            foreach (themeFontFamilyContractVarFallbacks($fontValue) as $fallback) {
                foreach (themeFontFamilyContractSplitTopLevel($fallback) as $family) {
                    $normalisedFamily = strtolower(trim($family, " \t\n\r\0\x0B'\""));

                    if (themeFontFamilyContractTerminatesInGeneric($family)) {
                        break;
                    }

                    expect(
                        ! in_array($normalisedFamily, themeFontFamilyContractKnownBundledFamilies(), true)
                        || preg_match('/\\A[\'\"].+[\'\"]\\z/', trim($family)) === 1,
                    )->toBeTrue(
                        basename($file) . " has an unquoted bundled font fallback: {$family}",
                    );
                }
            }
        }
    }
});

it('keeps the Foundation font bundle local, licensed, and WOFF2-only', function (): void {
    $packageRoot = dirname(__DIR__, 2);
    $fontStylesheet = file_get_contents($packageRoot . '/resources/css/fonts.css');
    $themeStylesheet = file_get_contents($packageRoot . '/resources/css/theme-foundation.css');
    $provider = file_get_contents($packageRoot . '/src/Providers/FoundationThemeServiceProvider.php');

    throw_unless(is_string($fontStylesheet), RuntimeException::class, 'Expected the Foundation font stylesheet to be readable.');
    throw_unless(is_string($themeStylesheet), RuntimeException::class, 'Expected the Foundation theme stylesheet to be readable.');
    throw_unless(is_string($provider), RuntimeException::class, 'Expected the Foundation service provider to be readable.');

    preg_match_all('/@font-face\s*\{([^}]*)\}/is', $fontStylesheet, $matches);

    expect($matches[1])->toHaveCount(15)
        ->and($themeStylesheet)->toContain("@import './fonts.css';")
        ->and($provider)->toContain("__DIR__ . '/../../resources/fonts' => public_path('vendor/capell-theme-foundation/fonts')");

    $fontFiles = [];

    foreach ($matches[1] as $fontFace) {
        preg_match("/url\('(?:[^']+\/)?([^']+\.woff2)'\)/i", $fontFace, $fontMatch);

        throw_unless(isset($fontMatch[1]), RuntimeException::class, 'Expected every Foundation font face to reference a WOFF2 asset.');

        $fontFile = $packageRoot . '/resources/fonts/' . $fontMatch[1];

        expect($fontFace)
            ->toContain('font-display: swap')
            ->toContain("format('woff2')");

        expect($fontFile)->toBeFile();
        $fontFiles[] = $fontFile;
    }

    expect(array_unique($fontFiles))->toHaveCount(15)
        ->and(glob($packageRoot . '/resources/fonts/licenses/*.txt') ?: [])->toHaveCount(13);
});
