<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Typography\ThemeFontCatalogue;

/** @return array<string, list<string>> font key => the theme providers that declare it */
function declaredThemeFontKeys(): array
{
    $keys = [];

    foreach (glob(dirname(__DIR__, 3) . '/theme-*/src/*ServiceProvider.php') ?: [] as $provider) {
        preg_match_all("/'(?:headingFont|bodyFont)'\s*=>\s*'([^']+)'/", (string) file_get_contents($provider), $matches);

        foreach ($matches[1] as $key) {
            $keys[$key][] = basename(dirname($provider, 2));
        }
    }

    return $keys;
}

function themeFontStylesheet(string $package): string
{
    return (string) file_get_contents(dirname(__DIR__, 3) . "/{$package}/resources/css/" . ($package === 'theme-foundation' ? 'fonts.css' : "{$package}.css"));
}

it('declares at least the font keys the shipped themes use', function (): void {
    expect(declaredThemeFontKeys())->not->toBeEmpty()->toHaveKeys(['inter', 'sora']);
});

it('resolves every font family a shipped theme declares to its own stack', function (): void {
    foreach (declaredThemeFontKeys() as $key => $themes) {
        expect(ThemeFontCatalogue::has($key))->toBeTrue("{$key} (" . implode(', ', $themes) . ') is not in the font catalogue');

        $stack = (new BrandProfileData(headingFont: $key))->tokens()['--theme-heading-font'];

        expect($stack)->toBe(ThemeFontCatalogue::all()[$key]['stack']);

        if ($key !== ThemeFontCatalogue::DEFAULT_KEY) {
            expect($stack)->not->toBe(ThemeFontCatalogue::stack(ThemeFontCatalogue::DEFAULT_KEY), "{$key} fell back to the default stack");
        }
    }
});

it('loads a font file for every catalogued family that ships one', function (): void {
    $fonts = themeFontStylesheet('theme-foundation');

    foreach (ThemeFontCatalogue::all() as $key => $entry) {
        if ($entry['file'] === null) {
            continue;
        }

        expect(dirname(__DIR__, 2) . '/resources/fonts/' . $entry['file'])->toBeFile()
            ->and($fonts)->toContain("font-family: '{$entry['family']}';", "url('" . ThemeFontCatalogue::preloadPath($key) . "')");
    }
});

it('self-hosts every catalogued family without a bundled file in the theme that names it', function (): void {
    foreach (ThemeFontCatalogue::all() as $key => $entry) {
        if ($entry['file'] !== null || $key === 'system') {
            continue;
        }

        $stylesheets = array_map(
            fn (string $package): string => themeFontStylesheet($package),
            array_unique(array_map(fn (string $theme): string => $theme, declaredThemeFontKeys()[$key] ?? [])),
        );

        expect(implode("\n", $stylesheets))->toContain("font-family: '{$entry['family']}';");
    }
});

it('keeps font loading non-blocking and every bundled face swap-displayed', function (): void {
    preg_match_all('/@font-face\s*\{([^}]*)\}/i', themeFontStylesheet('theme-foundation'), $faces);

    expect($faces[1])->not->toBeEmpty();

    foreach ($faces[1] as $face) {
        expect($face)->toContain('font-display: swap')->not->toContain('http');
    }
});
