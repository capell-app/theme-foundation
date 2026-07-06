<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Capell\FoundationTheme\Actions\ValidateThemeCatalogueEntryAction;
use Capell\FoundationTheme\Data\ThemeValidationResultData;
use Illuminate\Console\Command;

/**
 * Wave 1.4 — checks three-way agreement per theme: `capell.json` manifest,
 * `docs/themes.json` catalogue entry, and the theme's own registered
 * `ThemeDefinitionData` (obtained by calling that theme's service provider
 * `definition()` static method directly — see
 * {@see ValidateThemeCatalogueEntryAction}). Also checks `docs/screenshots.json`
 * manifest completeness and that catalogue classification fields are
 * populated.
 *
 * Delegates the per-theme check to {@see ValidateThemeCatalogueEntryAction} so
 * this command and the `ThemeCatalogueTest` / `ThemePackageManifestTest` Pest
 * suites share one source of truth. Needs nothing but Composer autoloading —
 * run via `vendor/bin/testbench capell:validate-themes` (registered through
 * this package's own `hasCommands()`), or directly via `scripts/validate-themes.php`
 * for the composer `manifest:check` chain, since `definition()` has no
 * framework or database dependency.
 *
 * Wave 0.7 sharpened the `docs/screenshots.json` check from a raw entry count
 * to gating on all 7 `ProvidesThemeDemoContent` surface names (homepage,
 * directory, detail, contact, empty, not-found, cta) — this table now
 * surfaces which of those a theme is missing by name, not just an entry count.
 */
class ValidateThemesCommand extends Command
{
    protected $signature = 'capell:validate-themes {theme? : Restrict validation to a single themeKey}';

    protected $description = 'Validate capell.json <-> docs/themes.json <-> ThemeDefinitionData agreement for every theme.';

    public function handle(): int
    {
        $packagesRoot = $this->packagesRoot();

        if ($packagesRoot === null) {
            $this->error('Could not locate the packages/ root; run this from the packages monorepo.');

            return self::FAILURE;
        }

        $themeDirectories = $this->themePackageDirectories($packagesRoot);

        if ($themeDirectories === []) {
            $this->error('No theme packages found under ' . $packagesRoot . '/theme-*.');

            return self::FAILURE;
        }

        $requestedThemeKey = $this->argument('theme');

        $results = [];

        foreach ($themeDirectories as $packageDirectory) {
            $result = ValidateThemeCatalogueEntryAction::run($packageDirectory, $packagesRoot);

            if (is_string($requestedThemeKey) && $result->themeKey !== $requestedThemeKey) {
                continue;
            }

            $results[] = $result;
        }

        if ($results === [] && is_string($requestedThemeKey)) {
            $this->error("No theme found for themeKey \"{$requestedThemeKey}\".");

            return self::FAILURE;
        }

        $this->renderTable($results);

        $failures = array_values(array_filter($results, fn (ThemeValidationResultData $result): bool => ! $result->passes()));

        if ($failures !== []) {
            $this->error(sprintf('%d of %d theme(s) failed validation.', count($failures), count($results)));

            return self::FAILURE;
        }

        $this->info(sprintf('All %d theme(s) passed validation.', count($results)));

        return self::SUCCESS;
    }

    /**
     * @param  list<ThemeValidationResultData>  $results
     */
    private function renderTable(array $results): void
    {
        $rows = array_map(
            fn (ThemeValidationResultData $result): array => [
                $result->themeKey,
                $result->passes() ? 'PASS' : 'FAIL',
                $result->passes() ? '' : implode("\n", $result->violations),
            ],
            $results,
        );

        $this->table(['Theme', 'Status', 'Violations'], $rows);
    }

    /**
     * @return list<string>
     */
    private function themePackageDirectories(string $packagesRoot): array
    {
        $manifestPaths = glob($packagesRoot . '/theme-*/capell.json') ?: [];
        sort($manifestPaths);

        $directories = [];

        foreach ($manifestPaths as $manifestPath) {
            $decoded = json_decode((string) file_get_contents($manifestPath), true);

            if (! is_array($decoded) || ($decoded['kind'] ?? null) !== 'theme') {
                continue;
            }

            $directories[] = basename(dirname($manifestPath));
        }

        return $directories;
    }

    private function packagesRoot(): ?string
    {
        $candidate = dirname(__DIR__, 4);

        if (is_dir($candidate) && glob($candidate . '/theme-*') !== []) {
            return $candidate;
        }

        if (function_exists('base_path')) {
            $fallback = base_path('packages');

            if (is_dir($fallback)) {
                return $fallback;
            }
        }

        return null;
    }
}
