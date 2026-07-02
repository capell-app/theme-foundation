<?php

declare(strict_types=1);

/*
 * Phase 5 render/safety guarantees that tie the catalogue (docs/themes.json)
 * to the shipped Blade. For every theme it proves:
 *   - every registered section renderer resolves to a Blade view that exists;
 *   - the catalogue's custom vs standard section split matches what each child
 *     theme's provider actually registers (custom sections have renderers,
 *     inherited standard sections resolve through Foundation);
 *   - public Blade carries no authoring/admin/package markers and runs no
 *     database queries.
 *
 * These are static source/filesystem checks — no application boot required —
 * so they run fast and deterministically in the theme-foundation suite.
 */

function themeCatalogueRoot(): string
{
    return dirname(__DIR__, 4);
}

/**
 * @return array<int, array<string, mixed>>
 */
function themeCatalogueEntries(): array
{
    $decoded = json_decode(
        (string) file_get_contents(themeCatalogueRoot() . '/docs/themes.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    throw_unless(is_array($decoded) && isset($decoded['themes']) && is_array($decoded['themes']), RuntimeException::class, 'docs/themes.json must contain a themes array.');

    return array_values(array_filter(
        $decoded['themes'],
        static fn (mixed $theme): bool => is_array($theme),
    ));
}

/**
 * Package directory ("theme-editorial-serif") for a catalogue package name
 * ("capell-app/theme-editorial-serif").
 */
function themePackageDirectory(string $packageName): string
{
    $segments = explode('/', $packageName);

    return end($segments) ?: $packageName;
}

/**
 * Extracts the [sectionKey => viewString] pairs registered by a theme
 * package's service provider(s) via ViewSectionRenderer.
 *
 * @return array<string, string>
 */
function themeSectionRenderers(string $packageDirectory): array
{
    $sourceDirectory = themeCatalogueRoot() . '/packages/' . $packageDirectory . '/src';

    $providerFiles = array_merge(
        glob($sourceDirectory . '/*ThemeServiceProvider.php') ?: [],
        glob($sourceDirectory . '/Providers/*ThemeServiceProvider.php') ?: [],
    );

    $source = '';

    foreach ($providerFiles as $providerFile) {
        $source .= file_get_contents($providerFile) ?: '';
    }

    preg_match_all(
        "/new ViewSectionRenderer\\(\\s*[^,]+,\\s*'([^']+)'\\s*,\\s*'([^']+)'/",
        $source,
        $matches,
        PREG_SET_ORDER,
    );

    $renderers = [];

    foreach ($matches as $match) {
        $renderers[$match[1]] = $match[2];
    }

    return $renderers;
}

/**
 * Resolves a Blade view string ("capell-theme-foundation::theme.chrome.footer")
 * to its absolute file path.
 */
function themeViewFilePath(string $viewString): ?string
{
    if (! str_contains($viewString, '::')) {
        return null;
    }

    [$namespace, $viewPath] = explode('::', $viewString, 2);
    $packageDirectory = str_starts_with($namespace, 'capell-') ? substr($namespace, 7) : $namespace;
    $relativePath = str_replace('.', '/', $viewPath);

    return themeCatalogueRoot() . '/packages/' . $packageDirectory . '/resources/views/' . $relativePath . '.blade.php';
}

/**
 * Concatenated public Blade for a theme package.
 */
function themePublicBlade(string $packageDirectory): string
{
    $viewsRoot = themeCatalogueRoot() . '/packages/' . $packageDirectory . '/resources/views';

    $bladeFiles = array_values(array_unique(array_merge(
        glob($viewsRoot . '/*.blade.php') ?: [],
        glob($viewsRoot . '/**/*.blade.php') ?: [],
        glob($viewsRoot . '/**/**/*.blade.php') ?: [],
    )));

    return implode(PHP_EOL, array_map(
        static fn (string $path): string => file_get_contents($path) ?: '',
        $bladeFiles,
    ));
}

it('resolves every registered section renderer to a Blade view that exists', function (): void {
    foreach (themeCatalogueEntries() as $theme) {
        $packageDirectory = themePackageDirectory((string) $theme['package']);
        $renderers = themeSectionRenderers($packageDirectory);

        expect($renderers)->not->toBeEmpty("{$packageDirectory} should register section renderers.");

        foreach ($renderers as $sectionKey => $viewString) {
            $viewFilePath = themeViewFilePath($viewString);

            expect($viewFilePath)->not->toBeNull("{$packageDirectory}:{$sectionKey} view string must be namespaced.");
            expect(is_file((string) $viewFilePath))->toBeTrue("{$packageDirectory}:{$sectionKey} -> {$viewString} must resolve to an existing Blade view.");
        }
    }
});

it('keeps the catalogue custom/standard split in sync with each child theme provider', function (): void {
    foreach (themeCatalogueEntries() as $theme) {
        // Foundation is the base theme; its own views are validated elsewhere.
        if (($theme['extends'] ?? null) === null) {
            continue;
        }

        $packageDirectory = themePackageDirectory((string) $theme['package']);
        $ownNamespace = 'capell-' . $packageDirectory . '::';
        $foundationNamespace = 'capell-theme-foundation::';

        $renderers = themeSectionRenderers($packageDirectory);

        $customFromSource = [];
        $standardFromSource = [];

        foreach ($renderers as $sectionKey => $viewString) {
            if (str_starts_with($viewString, $ownNamespace)) {
                $customFromSource[] = $sectionKey;
            } elseif (str_starts_with($viewString, $foundationNamespace)) {
                $standardFromSource[] = $sectionKey;
            }
        }

        sort($customFromSource);
        sort($standardFromSource);

        $catalogueCustom = $theme['customSections'] ?? [];
        $catalogueStandard = $theme['standardSections'] ?? [];

        throw_unless(is_array($catalogueCustom) && is_array($catalogueStandard), RuntimeException::class, "{$packageDirectory} catalogue sections must be arrays.");

        sort($catalogueCustom);
        sort($catalogueStandard);

        expect($customFromSource)->toBe($catalogueCustom, "{$packageDirectory} customSections must match its own-namespace renderers.")
            ->and($standardFromSource)->toBe($catalogueStandard, "{$packageDirectory} standardSections must match its Foundation-inherited renderers.");
    }
});

it('keeps every theme public Blade free of authoring and database markers', function (): void {
    $forbidden = [
        'wire:',
        'data-field',
        'data-model',
        'field_path',
        'model_id',
        'Filament',
        'Livewire',
        '::query(',
        'DB::',
        'loadMissing(',
    ];

    foreach (themeCatalogueEntries() as $theme) {
        // Foundation is the shared runtime and legitimately ships Livewire
        // admin/authoring widgets; child themes are the pure public surface.
        if (($theme['extends'] ?? null) === null) {
            continue;
        }

        $packageDirectory = themePackageDirectory((string) $theme['package']);
        $publicBlade = themePublicBlade($packageDirectory);

        foreach ($forbidden as $marker) {
            expect(str_contains($publicBlade, $marker))->toBeFalse("{$packageDirectory} public Blade must not contain '{$marker}'.");
        }
    }
});
