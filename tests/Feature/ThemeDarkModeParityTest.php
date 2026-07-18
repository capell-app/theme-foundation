<?php

declare(strict_types=1);

use Capell\FoundationTheme\Settings\FoundationThemeSettings;

/*
 * Reads `FoundationThemeSettings::$dark_surface_background_color`'s default
 * value via reflection, rather than instantiating the Settings class
 * (which requires a bound config repository) — this test is a pure static
 * colour-math guard and has no need to touch the settings container.
 */
function darkModeParityFoundationSettingDefault(string $propertyName, string $fallback): string
{
    $property = new ReflectionProperty(FoundationThemeSettings::class, $propertyName);
    $defaultValue = $property->getDefaultValue();

    return is_string($defaultValue) ? $defaultValue : $fallback;
}

function darkModeParityFoundationDefaultDarkSurfaceColor(): string
{
    return darkModeParityFoundationSettingDefault('dark_surface_background_color', '#101d1a');
}

/*
 * Wave 2.4 — dark-mode parity guard.
 *
 * Every `packages/theme-*` preset declares a `surfaceColor`/`foregroundColor`
 * pair in its ThemeDefinitionData (see `*ThemeServiceProvider::definition()`).
 * Some presets are already dark (e.g. theme-platform's default preset),
 * but every preset with a *light* surface colour renders through Foundation's
 * shared `.dark:root` token set (`tokens.blade.php`,
 * `ResolveFoundationThemeTokensAction`, `FoundationThemeSettings`'s
 * `dark_*_color` defaults) once the site is toggled into dark mode — there is
 * no per-theme dark-token resolver to reuse, so this test targets the one
 * Foundation resolves for the whole fleet.
 *
 * The assertions use the WCAG relative-luminance and contrast-ratio formulae.
 * Normal text token pairs must meet the AA 4.5:1 threshold in both schemes.
 */

/**
 * Parses `#rrggbb` / `#rgb` into 0-255 sRGB channel values.
 *
 * @return array{0: int, 1: int, 2: int}
 */
function darkModeParityHexToRgb(string $hex): array
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = implode('', array_map(static fn (string $channel): string => str_repeat($channel, 2), str_split($hex)));
    }

    return [
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    ];
}

/**
 * Relative luminance in the 0.0-1.0 range using the WCAG sRGB-to-linear
 * transfer function.
 */
function darkModeParityRelativeLuminance(string $hex): float
{
    [$red, $green, $blue] = darkModeParityHexToRgb($hex);

    $linearize = static function (int $channel): float {
        $normalized = $channel / 255;

        return $normalized <= 0.03928
            ? $normalized / 12.92
            : (($normalized + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * $linearize($red) + 0.7152 * $linearize($green) + 0.0722 * $linearize($blue);
}

function darkModeParityContrastRatio(string $foreground, string $background): float
{
    $foregroundLuminance = darkModeParityRelativeLuminance($foreground);
    $backgroundLuminance = darkModeParityRelativeLuminance($background);
    $lighter = max($foregroundLuminance, $backgroundLuminance);
    $darker = min($foregroundLuminance, $backgroundLuminance);

    return ($lighter + 0.05) / ($darker + 0.05);
}

/**
 * @return array<string, array{surfaceColor: string, foregroundColor: string}> presetLabel => colours
 */
function darkModeParityLightSurfacePresets(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $providerFiles = glob($packagesRoot . '/theme-*/src/*ThemeServiceProvider.php') ?: [];

    sort($providerFiles);

    $lightSurfacePresets = [];

    foreach ($providerFiles as $providerFile) {
        $packageDirectory = basename(dirname($providerFile, 2));
        $contents = file_get_contents($providerFile);

        if (! is_string($contents)) {
            continue;
        }

        preg_match_all('/\'surfaceColor\'\s*=>\s*\'(#[0-9A-Fa-f]{3,8})\'.*?\'foregroundColor\'\s*=>\s*\'(#[0-9A-Fa-f]{3,8})\'/s', $contents, $matches, PREG_SET_ORDER);

        foreach ($matches as $index => $match) {
            $surfaceColor = $match[1];
            $foregroundColor = $match[2];

            if (darkModeParityRelativeLuminance($surfaceColor) <= 0.5) {
                continue;
            }

            $lightSurfacePresets["{$packageDirectory}#{$index}"] = [
                'surfaceColor' => $surfaceColor,
                'foregroundColor' => $foregroundColor,
            ];
        }
    }

    return $lightSurfacePresets;
}

it('finds at least one light-surface preset in the fleet to guard', function (): void {
    expect(darkModeParityLightSurfacePresets())->not->toBeEmpty();
});

it('keeps every light-surface preset foreground at WCAG AA contrast', function (): void {
    foreach (darkModeParityLightSurfacePresets() as $presetLabel => $colours) {
        $contrastRatio = darkModeParityContrastRatio($colours['foregroundColor'], $colours['surfaceColor']);

        expect($contrastRatio)->toBeGreaterThanOrEqual(
            4.5,
            "{$presetLabel} has {$contrastRatio}:1 foreground contrast; normal text requires at least 4.5:1.",
        );
    }
});

it('resolves a legibly darker dark-mode surface for every light-surface preset', function (): void {
    $darkSurfaceLuminance = darkModeParityRelativeLuminance(darkModeParityFoundationDefaultDarkSurfaceColor());

    foreach (darkModeParityLightSurfacePresets() as $presetLabel => $colours) {
        $lightSurfaceLuminance = darkModeParityRelativeLuminance($colours['surfaceColor']);

        expect($darkSurfaceLuminance)->toBeLessThan(
            $lightSurfaceLuminance,
            "{$presetLabel}'s light surface ({$colours['surfaceColor']}) does not resolve a materially darker Foundation dark-mode surface — dark mode would not read as a real theme swap.",
        );
    }
});

it('resolves WCAG AA foreground contrast on the dark-mode surface', function (): void {
    $darkSurface = darkModeParityFoundationDefaultDarkSurfaceColor();
    $darkForeground = '#f8fafc';
    $contrastRatio = darkModeParityContrastRatio($darkForeground, $darkSurface);

    expect($contrastRatio)->toBeGreaterThanOrEqual(
        4.5,
        "Foundation dark mode has {$contrastRatio}:1 foreground contrast; normal text requires at least 4.5:1.",
    );
});

it('keeps primary actions at WCAG AA contrast in both colour schemes', function (): void {
    $lightSurface = darkModeParityFoundationSettingDefault('surface_background_color', '#fcfffb');
    $lightPrimaryAction = darkModeParityFoundationSettingDefault('primary_action_color', '#087765');
    $darkSurface = darkModeParityFoundationDefaultDarkSurfaceColor();
    $darkPrimaryAction = darkModeParityFoundationSettingDefault('dark_primary_action_color', '#79d7c2');

    expect(darkModeParityContrastRatio($lightPrimaryAction, $lightSurface))->toBeGreaterThanOrEqual(4.5)
        ->and(darkModeParityContrastRatio('#ffffff', $lightPrimaryAction))->toBeGreaterThanOrEqual(4.5)
        ->and(darkModeParityContrastRatio($darkPrimaryAction, $darkSurface))->toBeGreaterThanOrEqual(4.5);
});

/*
 * Fleet Blade scan: no blade file under any theme package's resources/views
 * directory may hardcode a hex colour literal outside a token-variable
 * definition. Foundation's own `tokens.blade.php` legitimately declares hex
 * defaults as the *source* of the CSS custom properties every theme
 * consumes, so it is exempted by path rather than by trying to parse CSS
 * declaration blocks out of Blade.
 */

/**
 * @return array<string, string> bladeFilePath => contents
 */
function darkModeParityFleetBladeFiles(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $exemptPathSuffixes = [
        '/theme-foundation/resources/views/components/app/head/tokens.blade.php',
    ];

    $bladeFiles = [];
    $viewDirectories = glob($packagesRoot . '/theme-*/resources/views', GLOB_ONLYDIR) ?: [];

    foreach ($viewDirectories as $viewDirectory) {
        $directoryIterator = new RecursiveDirectoryIterator($viewDirectory, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $path = $fileInfo->getPathname();

            if (! str_ends_with($path, '.blade.php')) {
                continue;
            }

            $isExempt = false;

            foreach ($exemptPathSuffixes as $exemptPathSuffix) {
                if (str_ends_with($path, $exemptPathSuffix)) {
                    $isExempt = true;

                    break;
                }
            }

            if ($isExempt) {
                continue;
            }

            $contents = file_get_contents($path);

            if (is_string($contents)) {
                $bladeFiles[$path] = $contents;
            }
        }
    }

    return $bladeFiles;
}

/*
 * Frozen baseline (2026-07-06): hardcoded hex literals found in Blade
 * markup that predates this test. This is a ratchet, not an allowlist —
 * these paths may be fixed and removed at any time, but no path outside
 * this list may introduce a new hardcoded hex literal. Tracked as
 * follow-up debt for a future hardening wave rather than blocking Wave 2.4
 * on a full fleet-wide token migration.
 *
 * @var list<string>
 */
const DARK_MODE_PARITY_FROZEN_HEX_VIOLATIONS = [
    '/theme-foundation/resources/views/components/demo/contact-page.blade.php',
    '/theme-foundation/resources/views/components/footer/index.blade.php',
    '/theme-foundation/resources/views/components/widget/asset/testimonials.blade.php',
    '/theme-foundation/resources/views/components/widget/asset/banners.blade.php',
    '/theme-foundation/resources/views/components/widget/modern/team-members.blade.php',
    '/theme-foundation/resources/views/components/lightbox.blade.php',
    '/theme-foundation/resources/views/components/layout/index.blade.php',
    '/theme-foundation/resources/views/components/layout/main.blade.php',
    '/theme-foundation/resources/views/theme/sections/content-listing.blade.php',
    '/theme-catalogue/resources/views/sections/hero.blade.php',
];

it('keeps the theme fleet free of new hardcoded hex colours in Blade markup', function (): void {
    foreach (darkModeParityFleetBladeFiles() as $path => $contents) {
        $isFrozenViolation = false;

        foreach (DARK_MODE_PARITY_FROZEN_HEX_VIOLATIONS as $frozenSuffix) {
            if (str_ends_with($path, $frozenSuffix)) {
                $isFrozenViolation = true;

                break;
            }
        }

        if ($isFrozenViolation) {
            continue;
        }

        // Excludes `&#9658;`-style numeric HTML character entities, which
        // share the `#<hex-digits>` shape but are not colour literals.
        expect(preg_match('/(?<!&)#[0-9a-fA-F]{3,8}\b/', $contents))->toBe(
            0,
            "{$path} contains a hardcoded hex colour literal; use a CSS custom property token instead of an inline hex value.",
        );
    }
});
