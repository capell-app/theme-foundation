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
