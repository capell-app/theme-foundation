<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Actions\ResolveBrandProfileAction;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemeOverrideData;

/*
 * CAP-0200 / CAP-0246: child themes used to ship the identical shared editor
 * schema, so a buyer could not configure anything about the theme they bought.
 * Each theme now declares an identity control of its own.
 *
 * ThemeEditorSchemaConsumptionTest already guards the six SHARED tokens. This
 * file guards the per-theme EXTRA tokens, which that test does not reach
 * because StandardThemeEditorSchema::tokenKeys() only walks definition().
 *
 * Three properties are enforced, and together they are what stops the fleet
 * from growing 28 hollow copy-pasted controls (CAP-0203, structural
 * duplication):
 *
 *   1. every option a theme offers has a real CSS consumer in that theme's own
 *      stylesheet, so no control is a no-op;
 *   2. every preset carries a value for the token, because a custom token is
 *      only emitted when a value is present — without this the control is
 *      invisible until someone touches it;
 *   3. no two themes declare the same identity token, so "give every theme a
 *      setting" cannot be satisfied by pasting one control 28 times.
 */

/**
 * @return array<string, ThemeDefinitionData> package directory => definition
 */
function identityControlThemeDefinitions(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $providerFiles = array_merge(
        glob($packagesRoot . '/theme-*/src/*ServiceProvider.php') ?: [],
        glob($packagesRoot . '/theme-*/src/Providers/*ServiceProvider.php') ?: [],
    );
    sort($providerFiles);

    $definitions = [];

    foreach ($providerFiles as $providerFile) {
        $packageDirectory = basename(dirname($providerFile, str_contains($providerFile, '/src/Providers/') ? 3 : 2));

        if ($packageDirectory === 'theme-foundation') {
            continue;
        }

        $source = file_get_contents($providerFile);

        if (! is_string($source)) {
            continue;
        }

        if (preg_match('/^namespace\s+([^;]+);/m', $source, $namespaceMatch) !== 1) {
            continue;
        }

        $class = trim($namespaceMatch[1]) . '\\' . basename($providerFile, '.php');

        if (! class_exists($class) || ! method_exists($class, 'definition')) {
            continue;
        }

        $definition = $class::definition();

        expect($definition)->toBeInstanceOf(ThemeDefinitionData::class);

        $definitions[$packageDirectory] = $definition;
    }

    return $definitions;
}

function identityControlStylesheet(string $packageDirectory): string
{
    $path = dirname(__DIR__, 3) . "/{$packageDirectory}/resources/css/{$packageDirectory}.css";
    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException("Expected a stylesheet at [{$path}].");
    }

    return $contents;
}

function identityControlCustomProperty(string $tokenKey): string
{
    $kebab = strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $tokenKey));

    return '--theme-' . $kebab;
}

/**
 * The tokens a theme declares beyond the shared schema and beyond the base
 * BrandProfileData surface — its own identity controls.
 *
 * @return array<string, array{options: list<string>}>
 */
function identityControlTokens(ThemeDefinitionData $definition): array
{
    $declarations = data_get($definition->frontend, 'editor.tokens', []);

    if (! is_array($declarations)) {
        return [];
    }

    $tokens = [];

    foreach ($declarations as $tokenKey => $declaration) {
        if (! is_string($tokenKey) || ! is_array($declaration)) {
            continue;
        }

        // Base tokens travel the BrandProfileData surface, not the custom-token
        // path, and are covered by ThemeEditorSchemaConsumptionTest.
        if (BrandProfileData::supportsToken($tokenKey)) {
            continue;
        }

        $options = $declaration['options'] ?? null;

        if (! is_array($options) || $options === []) {
            continue;
        }

        $typedOptions = [];
        foreach ($options as $option) {
            if (! is_string($option)) {
                throw new LogicException(sprintf('Theme [%s] declares a non-string identity option for [%s].', $tokenKey, $tokenKey));
            }

            $typedOptions[] = $option;
        }

        $tokens[$tokenKey] = ['options' => $typedOptions];
    }

    return $tokens;
}

it('discovers every child theme definition', function (): void {
    // Guards the harness itself: a broken glob or namespace regex would make
    // every assertion below vacuously pass.
    // 28 -> 27 on 2026-08-31: PR #782 retired the Photography theme into Brutalist's
    // `magazine-masthead` preset, leaving 28 theme packages of which 27 are child themes
    // (theme-foundation itself ships no child definition).
    expect(identityControlThemeDefinitions())->toHaveCount(27);
});

it('gives every child theme at least one configurable control', function (): void {
    foreach (identityControlThemeDefinitions() as $packageDirectory => $definition) {
        $declarations = data_get($definition->frontend, 'editor.tokens', []);

        expect($declarations)->toBeArray()
            ->and($declarations)->not->toBeEmpty(
                "Theme [{$packageDirectory}] exposes no Theme Studio controls at all.",
            );
    }
});

it('backs every identity option with a real CSS consumer in that theme', function (): void {
    $checked = 0;

    foreach (identityControlThemeDefinitions() as $packageDirectory => $definition) {
        $tokens = identityControlTokens($definition);

        if ($tokens === []) {
            continue;
        }

        $stylesheet = identityControlStylesheet($packageDirectory);

        foreach ($tokens as $tokenKey => $declaration) {
            $customProperty = identityControlCustomProperty($tokenKey);

            // str_contains rather than toContain(): toContain() is variadic, so
            // a failure message passed as a second argument silently becomes a
            // second needle and the assertion asserts the wrong thing.
            expect(str_contains($stylesheet, $customProperty))->toBeTrue(
                "Theme [{$packageDirectory}] declares identity token [{$tokenKey}] but never reads {$customProperty}.",
            );

            preg_match_all(
                '/style\(\s*' . preg_quote($customProperty, '/') . '\s*:\s*([a-z0-9-]+)\s*\)/',
                $stylesheet,
                $matches,
            );
            $consumedValues = array_values(array_unique($matches[1]));

            // One option may legitimately have no block of its own: the value
            // that matches the theme's unconditioned default styling. Every
            // other option must be reachable.
            $unconsumed = array_values(array_diff($declaration['options'], $consumedValues));

            expect(count($unconsumed))->toBeLessThanOrEqual(
                1,
                "Theme [{$packageDirectory}] token [{$tokenKey}] offers options with no CSS consumer: "
                    . implode(', ', $unconsumed) . '. At most one option may rely on the unconditioned default.',
            );

            $checked++;
        }
    }

    expect($checked)->toBeGreaterThan(0);
});

it('emits every identity token through the runtime brand profile', function (): void {
    foreach (identityControlThemeDefinitions() as $packageDirectory => $definition) {
        $tokens = identityControlTokens($definition);

        if ($tokens === []) {
            continue;
        }

        expect($definition->presets)->not->toBeEmpty();

        foreach ($tokens as $tokenKey => $declaration) {
            $customProperty = identityControlCustomProperty($tokenKey);

            foreach ($definition->presets as $preset) {
                $override = new ThemeOverrideData(
                    themeKey: $definition->key,
                    presetKey: $preset->key,
                    values: [],
                );

                $emitted = ResolveBrandProfileAction::run(new BrandProfileData, $definition, $override)->tokens();

                expect($emitted[$customProperty] ?? null)->toBeIn(
                    $declaration['options'],
                    "Theme [{$packageDirectory}] preset [{$preset->key}] emits no valid {$customProperty}. "
                        . 'A custom token is only rendered when the preset carries a value for it.',
                );
            }

            // A Theme Studio override must reach the rendered custom property
            // too, otherwise the admin control would change nothing.
            foreach ($declaration['options'] as $option) {
                $override = new ThemeOverrideData(
                    themeKey: $definition->key,
                    presetKey: $definition->presets[0]->key,
                    values: [$tokenKey => $option],
                );

                $emitted = ResolveBrandProfileAction::run(new BrandProfileData, $definition, $override)->tokens();

                expect($emitted[$customProperty] ?? null)->toBe(
                    $option,
                    "Theme [{$packageDirectory}] override [{$tokenKey}: {$option}] never reaches {$customProperty}.",
                );
            }
        }
    }
});

it('keeps identity controls distinct across the fleet', function (): void {
    $owners = [];

    foreach (identityControlThemeDefinitions() as $packageDirectory => $definition) {
        foreach (array_keys(identityControlTokens($definition)) as $tokenKey) {
            $owners[$tokenKey][] = $packageDirectory;
        }
    }

    foreach ($owners as $tokenKey => $themes) {
        expect($themes)->toHaveCount(
            1,
            "Identity token [{$tokenKey}] is declared by more than one theme (" . implode(', ', $themes)
                . '). A control shared by several themes belongs in StandardThemeEditorSchema, not copy-pasted '
                . 'per theme — see CAP-0203 on structural duplication.',
        );
    }
});
