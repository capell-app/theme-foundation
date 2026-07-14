<?php

declare(strict_types=1);

/*
 * Guards the Wave 5 section-variant contract: a theme may declare
 * `frontend['sectionVariants']` (see VariantViewSectionRenderer) naming which
 * variants a section supports. Every declared variant must resolve to a real
 * Blade view on disk, named `<section>--<variant>.blade.php` beside the base
 * `<section>.blade.php`, so a declared variant can never silently 404 into
 * the renderer's fallback markup.
 *
 * Phase C: a theme converted to render through x-capell::layout +
 * layout-builder has no sections/ directory and declares no sectionVariants,
 * so it is naturally skipped by the `! is_array($sectionVariants)` guard
 * below — no explicit exemption is needed for it.
 *
 * Wave 2.2: theme-foundation itself is the one theme whose section views
 * predate the `resources/views/sections/` convention used by every child
 * theme — its own base/variant Blade lives under
 * `resources/views/theme/sections/` instead (see
 * FoundationThemeServiceProvider::themeStudioSectionRenderers()). The path
 * candidates below check both locations so this single generic test covers
 * Foundation's own declared variants as well as every child theme's.
 */

require_once __DIR__ . '/../../../../tests/Packages/Support/ThemeManifestContracts.php';

/**
 * @return array<string, array{directory: string, providerClass: class-string}>
 */
function sectionVariantThemeProviders(): array
{
    $manifests = capell_theme_manifest_entries();
    $providers = [];

    foreach ($manifests as $entry) {
        $providerClasses = capell_theme_manifest_provider_classes($entry['manifest']);
        $providerClass = $providerClasses[0] ?? null;

        if ($providerClass === null || ! method_exists($providerClass, 'definition')) {
            continue;
        }

        $themeKey = $entry['manifest']['themeKey'] ?? null;

        if (! is_string($themeKey) || $themeKey === '') {
            continue;
        }

        $providers[$themeKey] = [
            'directory' => $entry['directory'],
            'providerClass' => $providerClass,
        ];
    }

    return $providers;
}

it('resolves every declared section variant to a real Blade view file', function (): void {
    $checked = 0;

    foreach (sectionVariantThemeProviders() as $themeKey => $entry) {
        /** @var class-string $providerClass */
        $providerClass = $entry['providerClass'];
        $definition = $providerClass::definition();
        $sectionVariants = $definition->frontend['sectionVariants'] ?? null;

        if (! is_array($sectionVariants)) {
            continue;
        }

        foreach ($sectionVariants as $section => $variants) {
            expect($variants)->toBeArray();

            if (! is_iterable($variants) || ! is_string($section)) {
                continue;
            }

            foreach ($variants as $variant) {
                if (! is_string($variant) || $variant === 'default') {
                    continue;
                }

                $checked++;
                $candidatePaths = [
                    $entry['directory'] . "/resources/views/sections/{$section}--{$variant}.blade.php",
                    $entry['directory'] . "/resources/views/theme/sections/{$section}--{$variant}.blade.php",
                ];
                $resolvedViewExists = collect($candidatePaths)->contains(static fn (string $candidatePath): bool => is_file($candidatePath));

                expect($resolvedViewExists)
                    ->toBeTrue(sprintf(
                        'Theme [%s] declares variant [%s] for section [%s] but none of [%s] exist.',
                        $themeKey,
                        $variant,
                        $section,
                        implode(', ', $candidatePaths),
                    ));
            }
        }
    }

    expect($checked)->toBeGreaterThan(0);
});

it('ships every Foundation hero variant with stable media and premium interaction states', function (): void {
    $themeRoot = dirname(__DIR__, 2);
    $heroViews = [
        'hero.blade.php',
        'hero--split.blade.php',
        'hero--stacked.blade.php',
        'hero--full-bleed.blade.php',
    ];

    foreach ($heroViews as $heroView) {
        $source = file_get_contents($themeRoot . '/resources/views/theme/sections/' . $heroView);

        expect($source)
            ->toContain('<h1')
            ->toContain('width="')
            ->toContain('height="')
            ->toContain('loading="eager"')
            ->toContain('fetchpriority="high"')
            ->toContain("\$action['url']")
            ->toContain("\$action['label']");
    }

    $styles = file_get_contents($themeRoot . '/resources/css/theme/theme.css');

    expect($styles)
        ->toContain('.theme-hero a:focus-visible')
        ->toContain('.theme-hero--full-bleed::after')
        ->toContain('calc(100svh - var(--header-height, 4.7rem))')
        ->toContain('@media (max-width: 767px)')
        ->toContain('.dark .theme-hero');
});

it('ships proof and conversion sections with resilient media and action states', function (): void {
    $themeRoot = dirname(__DIR__, 2);
    $features = file_get_contents($themeRoot . '/resources/views/theme/sections/features.blade.php');
    $proof = file_get_contents($themeRoot . '/resources/views/theme/sections/proof.blade.php');
    $styles = file_get_contents($themeRoot . '/resources/css/theme/theme.css');

    expect($features)
        ->toContain("\$feature['image_alt'] ?? \$feature['title']")
        ->toContain('width="800"')
        ->toContain('height="480"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');

    expect($proof)
        ->toContain("\$item['image_alt'] ?? \$item['title'] ?? \$item['name']")
        ->toContain('width="800"')
        ->toContain('height="320"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');

    expect($styles)
        ->toContain('.theme-features article:hover')
        ->toContain('.theme-proof figure')
        ->toContain('.theme-stats-display-band .count-up-stat')
        ->toContain('.theme-cta a:focus-visible')
        ->toContain('@media (prefers-reduced-motion: reduce)');
});

it('ships discovery sections with readable states and small-screen navigation', function (): void {
    $styles = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');

    expect($styles)
        ->toContain('.theme-content-listing :where(a, button):focus-visible')
        ->toContain('.theme-content-listing img')
        ->toContain('.theme-search form')
        ->toContain('.theme-search ul a:hover')
        ->toContain('.theme-pagination :where(a, span)')
        ->toContain('.theme-changelog-stream article')
        ->toContain('@media (max-width: 639px)');
});
