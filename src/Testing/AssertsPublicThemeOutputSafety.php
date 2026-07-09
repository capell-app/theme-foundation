<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Testing;

/**
 * Canonical frontend-authoring-safety assertions for theme Pest suites.
 *
 * Every `packages/theme-*` package ships a public Blade surface that must
 * never leak authoring/admin internals (package metadata, model IDs, field
 * paths, signed URLs, Livewire/Filament wiring) or reach into the database
 * directly from a view. This trait centralises the two banned-token
 * patterns that previously existed independently, near-verbatim, in every
 * per-theme `PublicOutputSafetyTest.php`:
 *
 * - The "classic" pattern (most themes): two checks — metadata across all
 *   Blade views, and a DB-query check across the same set.
 * - The "layout-native" pattern (theme-platform, theme-liquid-glass):
 *   three checks — metadata across all views (optionally including
 *   translation files and an inline-`<script>` ban), and a DB-query check
 *   scoped only to `resources/views/sections/*.blade.php`, the legacy inert
 *   section copies that receive pre-resolved data and must never call
 *   live Frontend::/DB/getMeta() APIs directly.
 *
 * Mix this trait into a Pest test file via `uses(AssertsPublicThemeOutputSafety::class);`
 * and call the relevant `assert*` method from an `it()` block.
 */
trait AssertsPublicThemeOutputSafety
{
    /**
     * Banned tokens for the classic two-check pattern's metadata assertion.
     *
     * @var list<string>
     */
    private static array $classicMetadataBannedTokens = [
        'authoring',
        'data-theme-key',
        'Filament',
        'Filament\\',
        'Livewire',
        'signed',
        'wire:',
        'data-field',
        'data-model',
        'field_path',
        'model_id',
        'permission',
        'capell-app/',
        'CapellCore::',
        'isPackageInstalled',
    ];

    /**
     * Banned tokens for the classic two-check pattern's DB-query assertion.
     *
     * @var list<string>
     */
    private static array $classicDatabaseBannedTokens = [
        '::query(',
        'DB::',
        'loadMissing(',
        'relationLoaded(',
        'Frontend::',
        'find(',
    ];

    /**
     * Banned tokens for the layout-native pattern's shared metadata assertion.
     *
     * @var list<string>
     */
    private static array $layoutNativeMetadataBannedTokens = [
        'authoring',
        'data-theme-key',
        'Filament',
        'Livewire',
        'signed',
        'wire:',
        'data-field',
        'data-model',
        'field_path',
        'model_id',
        'permission',
    ];

    /**
     * Banned tokens for the layout-native pattern's legacy-section DB-query
     * assertion — a superset of the classic list covering the additional
     * loader/facade/accessor surfaces available to layout-native themes.
     *
     * @var list<string>
     */
    private static array $layoutNativeSectionDatabaseBannedTokens = [
        '::query(',
        'DB::',
        'loadMissing(',
        'relationLoaded(',
        'getMeta(',
        'Frontend::',
        'PageLoader::',
        'SiteLoader::',
        'NavigationLoader::',
        '->translation',
        '->assets',
        '->media->',
        'find(',
    ];

    /**
     * Whitelisted static-call prefixes allowed inside `@php ... @endphp`
     * blocks in public theme Blade — pure helpers used for defaulting and
     * value prep, never queries or facades.
     *
     * @var list<string>
     */
    private static array $phpBlockWhitelistedStaticCallPrefixes = [
        'data_get',
        'collect',
        'trans',
        '__',
        'Str',
        'Arr',
    ];

    /**
     * Classic pattern: assert public Blade under `$viewsDirectory` is free
     * of authoring/package metadata and of direct database query calls.
     */
    protected function assertClassicThemeOutputIsSafe(string $viewsDirectory, string $packageName): void
    {
        $publicOutput = self::readBladeViewsRecursively($viewsDirectory);

        $this->assertStringNotContainsString($packageName, $publicOutput, 'Public Blade output must not reference its own package name.');

        foreach (self::$classicMetadataBannedTokens as $bannedToken) {
            $this->assertStringNotContainsString($bannedToken, $publicOutput, "Public Blade output must not contain the banned authoring/metadata token \"{$bannedToken}\".");
        }

        foreach (self::$classicDatabaseBannedTokens as $bannedToken) {
            $this->assertStringNotContainsString($bannedToken, $publicOutput, "Public Blade output must not contain the banned database-access token \"{$bannedToken}\".");
        }
    }

    /**
     * Layout-native pattern: assert public Blade (and optionally lang
     * files) under `$viewsDirectory` is free of authoring/package metadata
     * and inline scripts, and that the legacy `sections/*.blade.php` copies
     * are free of direct database/live-API calls.
     */
    protected function assertLayoutNativeThemeOutputIsSafe(string $viewsDirectory, string $packageName, bool $includeTranslations = false): void
    {
        $bladeViews = self::readBladeViewsRecursively($viewsDirectory);

        $publicOutput = $bladeViews;

        if ($includeTranslations) {
            $translationDirectory = rtrim($viewsDirectory, '/') . '/../lang/en';
            $publicOutput .= "\n" . self::readFilesMatchingGlob($translationDirectory . '/*.php');
        }

        $this->assertStringNotContainsString($packageName, $publicOutput, 'Public Blade output must not reference its own package name.');

        foreach (self::$layoutNativeMetadataBannedTokens as $bannedToken) {
            $this->assertStringNotContainsString($bannedToken, $publicOutput, "Public Blade output must not contain the banned authoring/metadata token \"{$bannedToken}\".");
        }

        if ($includeTranslations) {
            $this->assertStringNotContainsString('<script', $bladeViews, 'Public Blade output must not contain inline <script> tags.');
            $this->assertStringNotContainsString('</script>', $bladeViews, 'Public Blade output must not contain inline </script> tags.');
        }

        $legacySectionBlade = self::readFilesMatchingGlob(rtrim($viewsDirectory, '/') . '/sections/*.blade.php');

        foreach (self::$layoutNativeSectionDatabaseBannedTokens as $bannedToken) {
            $this->assertStringNotContainsString($bannedToken, $legacySectionBlade, "Legacy section Blade output must not contain the banned database/live-API token \"{$bannedToken}\".");
        }
    }

    /**
     * Fleet backstop: metadata-only check (no DB-query check, since
     * layout-native themes intentionally allow Frontend::/getMeta() calls
     * outside their legacy sections and a fleet-level test can't
     * distinguish which views are "legacy" per theme).
     *
     * `$exemptBannedTokens` exists solely for theme-foundation: unlike every
     * child theme, it is the base package that legitimately wires up
     * Livewire (`app.blade.php`'s `@livewireScripts`/`wire:navigate`
     * integration) for the whole fleet, so `Livewire`/`wire:` cannot be
     * banned from its own views the way they are for every theme built on
     * top of it. Every other banned token from the shared list still
     * applies to theme-foundation.
     */
    protected function assertThemeOutputMetadataIsSafe(string $viewsDirectory, string $packageName, array $exemptBannedTokens = []): void
    {
        $publicOutput = self::readBladeViewsRecursively($viewsDirectory);

        $this->assertStringNotContainsString($packageName, $publicOutput, 'Public Blade output must not reference its own package name.');

        foreach (self::$classicMetadataBannedTokens as $bannedToken) {
            if (in_array($bannedToken, $exemptBannedTokens, true)) {
                continue;
            }

            $this->assertStringNotContainsString($bannedToken, $publicOutput, "Public Blade output must not contain the banned authoring/metadata token \"{$bannedToken}\".");
        }
    }

    /**
     * `@php` block policy: freezes the per-theme `@php` block count as a
     * ratchet (may only decrease) and forbids any static call inside a
     * block body whose class-like prefix is not on the whitelist.
     *
     * `@php` is allowed only for defaulting/prep (`??=`, `data_get()`,
     * `@class` array prep); queries, facades, and side-effectful
     * conditionals must not appear inside `@php ... @endphp` blocks.
     *
     * `$additionalWhitelistedStaticCallPrefixes` exists solely for
     * theme-foundation and the layout-native themes (platform,
     * liquid-glass), whose live-pipeline chrome views (`app.blade.php`,
     * `header/index.blade.php`, `footer.blade.php`, `components/*.blade.php`)
     * legitimately call `Frontend::`, `Route::`, and
     * `GetPageVariablesAction::` — the same calls this trait's DB-query
     * checks already document as "the normal, safe, public-facing contract"
     * for that class of view, as opposed to the legacy
     * `sections/*.blade.php` copies where such calls remain banned.
     */
    protected function assertPhpBlockPolicy(string $viewsDirectory, int $frozenBaselineCount, array $additionalWhitelistedStaticCallPrefixes = []): void
    {
        $bladeViews = self::readBladeViewsRecursively($viewsDirectory);

        $currentPhpBlockCount = preg_match_all('/@php/', $bladeViews);

        $this->assertLessThanOrEqual(
            $frozenBaselineCount,
            $currentPhpBlockCount,
            "The number of @php blocks ({$currentPhpBlockCount}) must not exceed the frozen baseline ({$frozenBaselineCount}); @php usage may only decrease.",
        );

        $whitelistedStaticCallPrefixes = [...self::$phpBlockWhitelistedStaticCallPrefixes, ...$additionalWhitelistedStaticCallPrefixes];

        preg_match_all('/@php(.*?)@endphp/s', $bladeViews, $blockMatches);

        foreach ($blockMatches[1] as $blockBody) {
            $blockBodyWithoutStringLiterals = preg_replace(['/\'(?:[^\'\\\\]|\\\\.)*\'/s', '/"(?:[^"\\\\]|\\\\.)*"/s'], '', $blockBody) ?? $blockBody;

            preg_match_all('/(?<![A-Za-z0-9_\-])([A-Za-z_][A-Za-z0-9_]*)::/', $blockBodyWithoutStringLiterals, $staticCallMatches);

            foreach ($staticCallMatches[1] as $staticCallPrefix) {
                $this->assertContains(
                    $staticCallPrefix,
                    $whitelistedStaticCallPrefixes,
                    "Disallowed static call \"{$staticCallPrefix}::\" found inside an @php block; only defaulting/prep helpers (" . implode(', ', $whitelistedStaticCallPrefixes) . ') are permitted.',
                );
            }
        }
    }

    /**
     * Recursively reads every `*.blade.php` file under `$viewsDirectory`
     * (both the top level and nested subdirectories) and concatenates
     * their contents.
     */
    private static function readBladeViewsRecursively(string $viewsDirectory): string
    {
        $viewsDirectory = rtrim($viewsDirectory, '/');

        $paths = array_values(array_unique(array_merge(
            glob($viewsDirectory . '/*.blade.php') ?: [],
            glob($viewsDirectory . '/**/*.blade.php') ?: [],
        )));

        return implode(PHP_EOL, array_map(static fn (string $path): string => file_get_contents($path) ?: '', $paths));
    }

    /**
     * Reads and concatenates every file matching the given glob pattern.
     */
    private static function readFilesMatchingGlob(string $globPattern): string
    {
        $paths = glob($globPattern) ?: [];

        return implode(PHP_EOL, array_map(static fn (string $path): string => file_get_contents($path) ?: '', $paths));
    }
}
