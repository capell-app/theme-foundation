<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\Editor\StandardThemeEditorSchema;

/*
 * Wave 10.1: the shared editor schema a theme declares in
 * frontend['editor'] must be internally consistent AND every token it exposes
 * must actually reach that theme's rendered CSS — otherwise Theme Studio would
 * render a control that changes nothing. This guards the schema the same way
 * ThemeTokenConsumptionTest guards the raw preset tokens: structure first,
 * then a static consumption check over every theme's stylesheet.
 */

/**
 * @return array<string, string> packageDirectory => primary stylesheet contents
 */
function editorSchemaThemeStylesheets(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $stylesheetFiles = glob($packagesRoot . '/theme-*/resources/css/theme-*.css') ?: [];
    sort($stylesheetFiles);

    $stylesheets = [];

    foreach ($stylesheetFiles as $stylesheetFile) {
        $packageDirectory = basename(dirname($stylesheetFile, 3));

        if ($packageDirectory === 'theme-foundation') {
            continue;
        }

        $contents = file_get_contents($stylesheetFile);

        if (is_string($contents)) {
            $stylesheets[$packageDirectory] = $contents;
        }
    }

    return $stylesheets;
}

function editorSchemaFoundationStylesheet(): string
{
    $packagesRoot = dirname(__DIR__, 3);
    $stylesheetFiles = array_merge(
        glob($packagesRoot . '/theme-foundation/resources/css/**/*.css') ?: [],
        glob($packagesRoot . '/theme-foundation/resources/css/*.css') ?: [],
    );

    $combined = '';

    foreach ($stylesheetFiles as $stylesheetFile) {
        $contents = file_get_contents($stylesheetFile);

        if (is_string($contents)) {
            $combined .= $contents;
        }
    }

    return $combined;
}

/**
 * camelCase editor token key -> its --theme-<kebab> custom property name.
 */
function editorSchemaTokenCustomProperty(string $tokenKey): string
{
    $kebab = strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $tokenKey));

    return '--theme-' . $kebab;
}

/**
 * @return array<string, list<string>>
 */
function editorSchemaProviderPresetValues(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $providerFiles = array_merge(
        glob($packagesRoot . '/theme-*/src/*ThemeServiceProvider.php') ?: [],
        glob($packagesRoot . '/theme-*/src/Providers/*ThemeServiceProvider.php') ?: [],
    );
    $values = [];

    foreach (StandardThemeEditorSchema::tokenKeys() as $tokenKey) {
        $values[$tokenKey] = [];
    }

    foreach ($providerFiles as $providerFile) {
        $source = file_get_contents($providerFile);

        if (! is_string($source)) {
            continue;
        }

        foreach (array_keys($values) as $tokenKey) {
            preg_match_all("/'" . preg_quote($tokenKey, '/') . "'\\s*=>\\s*'([^']+)'/", $source, $matches);
            $values[$tokenKey] = [...$values[$tokenKey], ...$matches[1]];
        }
    }

    foreach ($values as $tokenKey => $tokenValues) {
        $values[$tokenKey] = array_values(array_unique($tokenValues));
        sort($values[$tokenKey]);
    }

    return $values;
}

/**
 * @return array<string, list<string>>
 */
function editorSchemaCssValues(): array
{
    $stylesheets = editorSchemaFoundationStylesheet() . implode("\n", editorSchemaThemeStylesheets());
    $values = [];

    foreach (StandardThemeEditorSchema::tokenKeys() as $tokenKey) {
        $customProperty = preg_quote(editorSchemaTokenCustomProperty($tokenKey), '/');
        preg_match_all('/style\\(' . $customProperty . ':\\s*([a-z0-9-]+)\\)/', $stylesheets, $matches);
        $values[$tokenKey] = array_values(array_unique($matches[1]));
        sort($values[$tokenKey]);
    }

    return $values;
}

it('declares a well-formed editor schema: every grouped token has options', function (): void {
    $schema = StandardThemeEditorSchema::definition();

    expect($schema)->toHaveKeys(['groups', 'tokens']);

    foreach ($schema['groups'] as $groupName => $groupTokens) {
        expect($groupTokens)->not->toBeEmpty("Editor group [{$groupName}] must reference at least one token.");

        foreach ($groupTokens as $tokenKey) {
            expect(array_key_exists($tokenKey, $schema['tokens']))
                ->toBeTrue("Grouped token [{$tokenKey}] has no entry in tokens[].");
            expect($schema['tokens'][$tokenKey]['options'])
                ->not->toBeEmpty("Token [{$tokenKey}] must offer at least one option.");
        }
    }
});

it('exposes only tokens that every theme actually consumes in CSS', function (): void {
    $foundationCss = editorSchemaFoundationStylesheet();
    $themeStylesheets = editorSchemaThemeStylesheets();

    expect($themeStylesheets)->not->toBeEmpty();

    foreach (StandardThemeEditorSchema::tokenKeys() as $tokenKey) {
        $customProperty = editorSchemaTokenCustomProperty($tokenKey);

        foreach ($themeStylesheets as $packageDirectory => $contents) {
            $consumed = str_contains($contents, $customProperty) || str_contains($foundationCss, $customProperty);

            expect($consumed)->toBeTrue(
                "Editor token [{$tokenKey}] resolves to {$customProperty}, but {$packageDirectory} never consumes it — the Theme Studio control would be a no-op.",
            );
        }
    }
});

it('keeps provider presets inside the editor vocabulary and CSS consumers', function (): void {
    $schema = StandardThemeEditorSchema::definition();
    $presetValues = editorSchemaProviderPresetValues();
    $cssValues = editorSchemaCssValues();
    $unconditionedDefaults = [
        'spacing' => 'balanced',
        'layoutPresentation' => 'structured',
        'cardStyle' => 'flat',
        'cardDensity' => 'comfortable',
        'headingScale' => 'balanced',
        'overlayTreatment' => 'subtle',
    ];

    foreach ($schema['tokens'] as $tokenKey => $definition) {
        $consumedValues = array_values(array_unique([
            ...$cssValues[$tokenKey],
            $unconditionedDefaults[$tokenKey],
        ]));

        foreach ($presetValues[$tokenKey] as $presetValue) {
            expect(in_array($presetValue, $definition['options'], true))->toBeTrue(
                "Provider preset [{$tokenKey}: {$presetValue}] is not available in the shared editor schema.",
            );
            expect(in_array($presetValue, $consumedValues, true))->toBeTrue(
                "Provider preset [{$tokenKey}: {$presetValue}] has no CSS consumer.",
            );
        }

        foreach ($definition['options'] as $option) {
            expect(in_array($option, $consumedValues, true))->toBeTrue(
                "Editor option [{$tokenKey}: {$option}] has no CSS consumer.",
            );
        }
    }
});
