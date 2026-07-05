<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Data\GenericSectionData;

require_once __DIR__ . '/../../../../tests/Packages/Support/ThemeLayoutNativeSupport.php';

/*
 * Wave 11.1 theme-switch resilience guard.
 *
 * Custom section types a theme's demo content uses (e.g. 'creator-hero',
 * 'discipline-filters') are never dedicated ThemeSection classes — at
 * render time Capell\Core\ThemeStudio\Data\GenericSectionData wraps them,
 * and CapellFrontendThemePageAdapter::genericSectionFromEntry() defaults
 * its fallbackKey() to 'content-listing' whenever demo data doesn't name
 * an explicit fallback. BladeThemeRenderer::renderSection() throws a hard
 * SectionRendererNotFoundException if neither the section's own key() nor
 * its fallbackKey() resolves to a registered renderer — so a site that
 * switches away from a theme with unmatched custom sections only survives
 * if the NEW theme has a renderer registered for 'content-listing'.
 *
 * This guard proves every theme in the catalogue carries that universal
 * safety net. It deliberately re-implements the small enumeration helpers
 * ThemeCatalogueRenderingTest.php also defines (themeCatalogueEntries(),
 * themePackageDirectory(), themeSectionRenderers()) under uniquely-named
 * functions rather than reusing them: Pest's global function scope is only
 * populated once every test file in a run is loaded, so a file exercised in
 * isolation (`pest path/to/this/file.php`) would otherwise fail with
 * "Call to undefined function" even though the full-suite run passes.
 *
 * Phase C: a theme converted to render through x-capell::layout +
 * layout-builder (see themesConvertedToLayoutBuilder()) registers no section
 * renderers at all — including 'content-listing' — because the fallback
 * concept only applies to the legacy section-rendering pipeline it no
 * longer uses. Converted themes are exempt from this guard for that reason.
 */

/**
 * @return array<int, array<string, mixed>>
 */
function themeFallbackCatalogueEntries(): array
{
    $decoded = json_decode(
        (string) file_get_contents(dirname(__DIR__, 4) . '/docs/themes.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    throw_unless(is_array($decoded) && isset($decoded['themes']) && is_array($decoded['themes']), RuntimeException::class, 'docs/themes.json must contain a themes array.');

    $themes = [];

    foreach ($decoded['themes'] as $theme) {
        if (is_array($theme)) {
            $themes[] = $theme;
        }
    }

    return $themes;
}

function themeFallbackPackageDirectory(string $packageName): string
{
    $segments = explode('/', $packageName);

    return end($segments) ?: $packageName;
}

/**
 * @return array<string, string>
 */
function themeFallbackSectionRenderers(string $packageDirectory): array
{
    $sourceDirectory = dirname(__DIR__, 4) . '/packages/' . $packageDirectory . '/src';

    $providerFiles = array_merge(
        glob($sourceDirectory . '/*ThemeServiceProvider.php') ?: [],
        glob($sourceDirectory . '/Providers/*ThemeServiceProvider.php') ?: [],
    );

    $source = '';

    foreach ($providerFiles as $providerFile) {
        $source .= file_get_contents($providerFile) ?: '';
    }

    preg_match_all("/new ViewSectionRenderer\\(\\s*[^,]+,\\s*'([^']+)'\\s*,\\s*'([^']+)'/", $source, $matches, PREG_SET_ORDER);

    $renderers = [];

    foreach ($matches as $match) {
        $renderers[$match[1]] = $match[2];
    }

    preg_match_all("/new VariantViewSectionRenderer\\(\\s*.*?sectionKey:\\s*'([^']+)'.*?baseView:\\s*'([^']+)'/s", $source, $variantMatches, PREG_SET_ORDER);

    foreach ($variantMatches as $match) {
        $renderers[$match[1]] = $match[2];
    }

    return $renderers;
}

it('registers a content-listing renderer so any theme can absorb another theme\'s custom sections on switch', function (): void {
    foreach (themeFallbackCatalogueEntries() as $theme) {
        $themeKey = $theme['themeKey'] ?? null;

        if (is_string($themeKey) && in_array($themeKey, themesConvertedToLayoutBuilder(), true)) {
            continue;
        }

        $package = $theme['package'] ?? null;
        throw_unless(is_string($package), RuntimeException::class, 'Theme catalogue package must be a string.');

        $packageDirectory = themeFallbackPackageDirectory($package);
        $renderers = themeFallbackSectionRenderers($packageDirectory);

        expect(array_key_exists('content-listing', $renderers))->toBeTrue(
            "{$packageDirectory} must register a 'content-listing' section renderer — it is the default fallbackKey() every custom section degrades to when switching away from a theme that no longer has a matching renderer.",
        );
    }
});

it('locks in GenericSectionData\'s content-listing default so the fleet-wide safety net cannot silently regress', function (): void {
    $section = new GenericSectionData(type: 'creator-hero', data: ['heading' => 'Any custom section']);

    expect($section->fallbackKey())->toBe('content-listing');
});
