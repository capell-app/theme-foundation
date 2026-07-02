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

    return $decoded;
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

    $catalogueKeys = collect($catalogue['themes'])
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

    foreach ($catalogue['themes'] as $theme) {
        throw_unless(is_array($theme), RuntimeException::class, 'Each catalogue theme must be an object.');

        $label = is_string($theme['themeKey'] ?? null) ? $theme['themeKey'] : '(unknown theme)';

        expect($theme['themeKey'] ?? null, "themeKey for {$label}")->toBeString()
            ->and($theme['package'] ?? null, "package for {$label}")->toBeString()
            ->and($theme['displayName'] ?? null, "displayName for {$label}")->toBeString()
            ->and($theme['tier'] ?? null, "tier for {$label}")->toBeIn($allowedTiers)
            ->and($theme['family'] ?? null, "family for {$label}")->toBeString()
            ->and($theme['family'] ?? '', "family for {$label}")->not->toBe('')
            ->and($theme['lane'] ?? null, "lane for {$label}")->toBeString()
            ->and($theme['lane'] ?? '', "lane for {$label}")->not->toBe('')
            ->and($theme['overlapRisk'] ?? null, "overlapRisk for {$label}")->toBeIn($allowedOverlap)
            ->and($theme['customisationSurfaces'] ?? null, "customisationSurfaces for {$label}")->toBeArray()
            ->and($theme['customisationSurfaces'] ?? [], "customisationSurfaces for {$label}")->not->toBeEmpty();
    }
});

it('records the customisation surfaces the differentiation contract depends on', function (): void {
    $catalogue = themeCatalogue();

    foreach ($catalogue['themes'] as $theme) {
        throw_unless(is_array($theme), RuntimeException::class, 'Each catalogue theme must be an object.');

        $surfaces = $theme['customisationSurfaces'] ?? [];
        $label = is_string($theme['themeKey'] ?? null) ? $theme['themeKey'] : '(unknown theme)';

        throw_unless(is_array($surfaces), RuntimeException::class, "customisationSurfaces for {$label} must be an object.");

        expect($surfaces, "customisationSurfaces keys for {$label}")
            ->toHaveKeys(['header', 'footer', 'themeStudioTokens']);
    }
});
