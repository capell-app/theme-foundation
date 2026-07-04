<?php

declare(strict_types=1);

/*
 * Guards the Wave 5 section-variant contract: a theme may declare
 * `frontend['sectionVariants']` (see VariantViewSectionRenderer) naming which
 * variants a section supports. Every declared variant must resolve to a real
 * Blade view on disk, named `<section>--<variant>.blade.php` beside the base
 * `<section>.blade.php`, so a declared variant can never silently 404 into
 * the renderer's fallback markup.
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
                $viewPath = $entry['directory'] . "/resources/views/sections/{$section}--{$variant}.blade.php";

                expect(is_file($viewPath))
                    ->toBeTrue(sprintf(
                        'Theme [%s] declares variant [%s] for section [%s] but %s does not exist.',
                        $themeKey,
                        $variant,
                        $section,
                        $viewPath,
                    ));
            }
        }
    }

    expect($checked)->toBeGreaterThan(0);
});
