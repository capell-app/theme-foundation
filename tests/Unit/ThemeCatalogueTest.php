<?php

declare(strict_types=1);

/*
 * Validates docs/themes.json — the canonical planning catalogue for the
 * first-party theme lanes. This is planning/catalogue metadata only; nothing
 * in the runtime depends on it. The test guarantees the catalogue stays in
 * lock-step with the shipped theme packages so new themes cannot be added
 * without a catalogue entry, and every entry declares the classification
 * fields the differentiation programme relies on.
 */

use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePresetData;
use Capell\FoundationTheme\Actions\ValidateThemeCatalogueEntryAction;

require_once __DIR__ . '/../Support/ThemeCatalogueScreenshotSurfaceGap.php';

/**
 * @return array<string, mixed>
 */
function themeCatalogueJson(string $path): array
{
    throw_unless(is_file($path), RuntimeException::class, 'Expected JSON file at ' . $path . '.');

    $decoded = json_decode(
        (string) file_get_contents($path),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    throw_unless(is_array($decoded), RuntimeException::class, 'Expected a JSON object at ' . $path . '.');

    return array_filter($decoded, static fn (mixed $value): bool => true);
}

/**
 * @return array<int, array<string, mixed>>
 */
function themeCatalogueThemeEntries(): array
{
    $themes = themeCatalogue()['themes'] ?? [];

    throw_unless(is_array($themes), RuntimeException::class, 'Expected catalogue themes array.');

    $entries = [];

    foreach ($themes as $theme) {
        throw_unless(is_array($theme), RuntimeException::class, 'Each catalogue theme must be an object.');

        $entries[] = capell_string_keyed_array($theme);
    }

    return $entries;
}

/**
 * @return array<string, mixed>
 */
function themeCatalogue(): array
{
    return themeCatalogueJson(dirname(__DIR__, 4) . '/docs/themes.json');
}

/**
 * @return array<string, mixed>
 */
function themeCatalogueStringKeyedArray(mixed $value): array
{
    return is_array($value) ? capell_string_keyed_array($value) : [];
}

/**
 * @return list<string>
 */
function shippedThemeKeys(): array
{
    $packagesDirectory = dirname(__DIR__, 3);
    $themeManifests = glob($packagesDirectory . '/theme-*/capell.json') ?: [];

    sort($themeManifests);

    $themeKeys = [];

    foreach ($themeManifests as $manifestPath) {
        $manifest = themeCatalogueJson($manifestPath);

        if (($manifest['kind'] ?? null) !== 'theme') {
            continue;
        }

        $themeKey = $manifest['themeKey'] ?? null;

        if (is_string($themeKey)) {
            $themeKeys[] = $themeKey;
        }
    }

    return $themeKeys;
}

it('declares a versioned catalogue envelope', function (): void {
    $catalogue = themeCatalogue();

    expect($catalogue)->toHaveKeys(['schemaVersion', 'lastReviewed', 'themes'])
        ->and($catalogue['schemaVersion'])->toBeInt()
        ->and($catalogue['lastReviewed'])->toBeString()
        ->and($catalogue['themes'])->toBeArray()
        ->and($catalogue['themes'])->not->toBeEmpty();
});

it('lists every shipped theme package exactly once', function (): void {
    $catalogue = themeCatalogue();

    $themes = $catalogue['themes'] ?? [];
    throw_unless(is_array($themes), RuntimeException::class, 'Expected catalogue themes array.');

    $catalogueKeys = collect($themes)
        ->map(fn (mixed $theme): mixed => is_array($theme) ? ($theme['themeKey'] ?? null) : null)
        ->filter(fn (mixed $key): bool => is_string($key))
        ->values();

    $shippedKeys = collect(shippedThemeKeys());

    // Every shipped package has a catalogue entry.
    foreach ($shippedKeys as $shippedKey) {
        expect($catalogueKeys)->toContain($shippedKey);
    }

    // The catalogue introduces no phantom themes.
    foreach ($catalogueKeys as $catalogueKey) {
        expect($shippedKeys)->toContain($catalogueKey);
    }

    // themeKey is unique across the catalogue.
    expect($catalogueKeys->duplicates())->toBeEmpty()
        ->and($catalogueKeys->count())->toBe($shippedKeys->count());
});

it('declares the required classification fields for every theme', function (): void {
    $catalogue = themeCatalogue();

    $allowedTiers = ['foundation', 'free', 'premium', 'experimental', 'candidate-for-merge'];
    $allowedOverlap = ['low', 'medium', 'high'];

    foreach (themeCatalogueThemeEntries() as $theme) {
        $label = is_string($theme['themeKey'] ?? null) ? $theme['themeKey'] : '(unknown theme)';

        expect($theme['themeKey'] ?? null)->toBeString("themeKey for {$label}")
            ->and($theme['package'] ?? null)->toBeString("package for {$label}")
            ->and($theme['displayName'] ?? null)->toBeString("displayName for {$label}")
            ->and($theme['tier'] ?? null)->toBeIn($allowedTiers, "tier for {$label}")
            ->and($theme['family'] ?? null)->toBeString("family for {$label}")
            ->and($theme['family'] ?? '')->not->toBe('', "family for {$label}")
            ->and($theme['lane'] ?? null)->toBeString("lane for {$label}")
            ->and($theme['lane'] ?? '')->not->toBe('', "lane for {$label}")
            ->and($theme['overlapRisk'] ?? null)->toBeIn($allowedOverlap, "overlapRisk for {$label}")
            ->and($theme['customisationSurfaces'] ?? null)->toBeArray("customisationSurfaces for {$label}")
            ->and($theme['customisationSurfaces'] ?? [])->not->toBeEmpty("customisationSurfaces for {$label}");
    }
});

it('records the customisation surfaces the differentiation contract depends on', function (): void {
    foreach (themeCatalogueThemeEntries() as $theme) {
        $surfaces = $theme['customisationSurfaces'] ?? [];
        $label = is_string($theme['themeKey'] ?? null) ? $theme['themeKey'] : '(unknown theme)';

        throw_unless(is_array($surfaces), RuntimeException::class, "customisationSurfaces for {$label} must be an object.");

        expect($surfaces)
            ->toHaveKeys(['header', 'footer', 'themeStudioTokens'], "customisationSurfaces keys for {$label}");
    }
});

it('publishes buyer inclusion facts for every theme and keeps demo profiles source-backed', function (): void {
    $root = dirname(__DIR__, 4);
    $catalogue = themeCatalogue();
    $buyerInclusions = themeCatalogueStringKeyedArray($catalogue['buyerInclusions'] ?? []);
    $manifestPaths = glob($root . '/packages/theme-*/capell.json') ?: [];
    $manifestsByThemeKey = [];

    foreach ($manifestPaths as $manifestPath) {
        $manifest = themeCatalogueJson($manifestPath);
        $themeKey = $manifest['themeKey'] ?? null;

        if (is_string($themeKey)) {
            $manifestsByThemeKey[$themeKey] = $manifest;
        }
    }

    expect($buyerInclusions)->toHaveCount(count($manifestsByThemeKey));

    foreach (themeCatalogueThemeEntries() as $theme) {
        $themeKey = $theme['themeKey'] ?? null;

        throw_unless(is_string($themeKey), RuntimeException::class, 'Each theme must have a string themeKey.');

        $inclusions = themeCatalogueStringKeyedArray($buyerInclusions[$themeKey] ?? []);
        $manifest = themeCatalogueStringKeyedArray($manifestsByThemeKey[$themeKey] ?? []);

        expect($inclusions)
            ->toHaveKeys(['demoProfiles', 'versionUpgrade', 'figmaSource', 'updates']);

        $profiles = $inclusions['demoProfiles'] ?? [];
        $providers = themeCatalogueStringKeyedArray($manifest['providers'] ?? []);
        $runtimeProviders = is_array($providers['runtime'] ?? null) ? $providers['runtime'] : [];
        $providerClass = $runtimeProviders[0] ?? null;

        expect($profiles)
            ->not->toBeEmpty()
            ->and($providerClass)->toBeString();

        throw_unless(is_string($providerClass) && class_exists($providerClass), RuntimeException::class, "Runtime provider for {$themeKey} must be a class.");

        $definition = $providerClass::definition();
        throw_unless($definition instanceof ThemeDefinitionData, RuntimeException::class, "Runtime provider for {$themeKey} must return a theme definition.");

        $sourceProfiles = [];

        foreach ($definition->presets as $preset) {
            throw_unless($preset instanceof ThemePresetData, RuntimeException::class, "Preset for {$themeKey} must be a ThemePresetData.");

            $sourceProfiles[] = [
                'id' => $preset->key,
                'label' => $preset->name,
                'preset' => $preset->key,
            ];
        }

        $versionUpgrade = themeCatalogueStringKeyedArray($inclusions['versionUpgrade'] ?? []);
        $figmaSource = themeCatalogueStringKeyedArray($inclusions['figmaSource'] ?? []);
        $updates = themeCatalogueStringKeyedArray($inclusions['updates'] ?? []);
        $manifestName = $manifest['name'] ?? null;
        $manifestVersion = $manifest['version'] ?? null;

        throw_unless(is_string($manifestName), RuntimeException::class, "Manifest name for {$themeKey} must be a string.");
        throw_unless(is_string($manifestVersion), RuntimeException::class, "Manifest version for {$themeKey} must be a string.");
        $majorMinorVersion = implode('.', array_slice(explode('.', $manifestVersion), 0, 2));

        expect($profiles)->toBe($sourceProfiles)
            ->and($versionUpgrade['current'] ?? null)->toBe($manifestVersion)
            ->and($versionUpgrade['scheme'] ?? null)->toBe('semver')
            ->and($versionUpgrade['package'] ?? null)->toBe($manifestName)
            ->and($versionUpgrade['compatibility'] ?? null)->toBe([
                'capellApi' => '^1.0',
                'php' => '>=8.4',
            ])
            ->and($versionUpgrade['notes'] ?? null)->toBe(['Initial stable release; no migration required.'])
            ->and($versionUpgrade['path'] ?? null)->toBe('composer require ' . $manifestName . ':^' . $majorMinorVersion)
            ->and($figmaSource['status'] ?? null)->toBe('not_included')
            ->and($updates['durationMonths'] ?? null)->toBe(12);

        expect($versionUpgrade['notes'] ?? null)->toBeArray()->not->toBeEmpty();

        $commands = themeCatalogueStringKeyedArray($manifest['commands'] ?? []);
        $demoCommand = $commands['demo'] ?? null;
        throw_unless(is_string($demoCommand), RuntimeException::class, "Demo command for {$themeKey} must be declared.");

        $commandFiles = glob($root . '/packages/theme-*/src/Console/Commands/*DemoCommand.php') ?: [];
        $commandSource = implode("\n", array_map(static fn (string $path): string => (string) file_get_contents($path), $commandFiles));

        expect($commandSource)->toContain('{--profile=');
    }
});

it('agrees with capell.json, ThemeDefinitionData, and docs/screenshots.json for every shipped theme', function (): void {
    // Wave 1.4 — ValidateThemeCatalogueEntryAction is the extracted,
    // single-source-of-truth version of the capell.json <-> docs/themes.json
    // <-> ThemeDefinitionData <-> docs/screenshots.json cross-check that this
    // file and ThemePackageManifestTest previously duplicated inline. Also
    // exercised directly by `capell:validate-themes` and
    // `scripts/validate-themes.php`.
    $packagesDirectory = dirname(__DIR__, 3);

    foreach (shippedThemeKeys() as $themeKey) {
        $manifestPaths = glob($packagesDirectory . '/theme-*/capell.json') ?: [];

        $packageDirectory = null;

        foreach ($manifestPaths as $manifestPath) {
            $manifest = themeCatalogueJson($manifestPath);

            if (($manifest['themeKey'] ?? null) === $themeKey) {
                $packageDirectory = basename(dirname($manifestPath));

                break;
            }
        }

        throw_unless(is_string($packageDirectory), RuntimeException::class, "Could not resolve package directory for themeKey {$themeKey}.");

        $result = ValidateThemeCatalogueEntryAction::run($packageDirectory, $packagesDirectory);
        $violations = themeCatalogueViolationsExcludingKnownScreenshotSurfaceGap($themeKey, $result->violations);

        expect($violations)->toBe([], "Theme \"{$themeKey}\" failed ValidateThemeCatalogueEntryAction: " . implode(' ', $violations));
    }
});
