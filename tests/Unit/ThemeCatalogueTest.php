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

        $entries[] = $theme;
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
