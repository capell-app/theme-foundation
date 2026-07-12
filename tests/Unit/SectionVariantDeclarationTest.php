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
