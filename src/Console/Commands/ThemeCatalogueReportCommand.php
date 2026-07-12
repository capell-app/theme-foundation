<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Console\Commands;

use Illuminate\Console\Command;

/**
 * Wave 11.8 — makes the theme catalogue self-auditing.
 *
 * Measures each theme's stylesheet against the fleet's craft contract
 * (CSS budget, and reduced-motion / dark / print / forced-colors /
 * container-query / view-transition coverage), counts its section blades,
 * and writes a scoreboard to docs/. It also validates the hand-maintained
 * docs/themes.json against measured reality so drift surfaces in the report
 * rather than rotting silently — the existing ThemeCatalogueTest gate then
 * keeps the two in sync.
 *
 * Read-only against theme source; the only thing it writes is the report.
 */
class ThemeCatalogueReportCommand extends Command
{
    /**
     * Per-theme CSS line budget — kept in step with ThemeCssIsolationTest.
     */
    private const CSS_LINE_BUDGET = 1800;

    protected $signature = 'capell:theme-catalogue-report {--write : Write the scoreboard to docs/theme-catalogue-report.md instead of printing it}';

    protected $description = 'Measure per-theme craft coverage and validate docs/themes.json against source.';

    public function handle(): int
    {
        $packagesRoot = $this->packagesRoot();

        if ($packagesRoot === null) {
            $this->error('Could not locate the packages/ root; run this from the packages monorepo.');

            return self::FAILURE;
        }

        $metrics = $this->collectThemeMetrics($packagesRoot);

        if ($metrics === []) {
            $this->error('No theme stylesheets found under ' . $packagesRoot . '/theme-*.');

            return self::FAILURE;
        }

        $catalogueDrift = $this->catalogueDrift($packagesRoot, $metrics);
        $report = $this->renderReport($metrics, $catalogueDrift);

        if ($this->option('write')) {
            $reportPath = dirname($packagesRoot) . '/docs/theme-catalogue-report.md';
            file_put_contents($reportPath, $report);
            $this->info('Wrote catalogue report to ' . $reportPath);
        } else {
            foreach (explode("\n", $report) as $reportLine) {
                $this->line($reportLine);
            }
        }

        // A budget overrun or catalogue drift is a soft failure: the report
        // still renders, but the exit code lets CI treat it as actionable.
        $overBudget = array_filter($metrics, fn (array $theme): bool => $theme['cssLines'] > self::CSS_LINE_BUDGET);

        if ($overBudget !== [] || $catalogueDrift !== []) {
            $this->warn(sprintf(
                '%d theme(s) over the CSS budget, %d catalogue drift note(s) — see the report.',
                count($overBudget),
                count($catalogueDrift),
            ));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectThemeMetrics(string $packagesRoot): array
    {
        $stylesheetFiles = glob($packagesRoot . '/theme-*/resources/css/theme-*.css') ?: [];
        sort($stylesheetFiles);

        $metrics = [];

        foreach ($stylesheetFiles as $stylesheetFile) {
            $packageDirectory = basename(dirname($stylesheetFile, 3));

            if ($packageDirectory === 'theme-foundation') {
                continue;
            }

            $contents = file_get_contents($stylesheetFile);

            if (! is_string($contents)) {
                continue;
            }

            $themeSlug = substr($packageDirectory, strlen('theme-'));
            $sectionsDirectory = $packagesRoot . '/' . $packageDirectory . '/resources/views/sections';

            $metrics[] = [
                'slug' => $themeSlug,
                'cssLines' => substr_count($contents, "\n") + 1,
                'sections' => $this->countSectionBlades($sectionsDirectory),
                'reducedMotion' => str_contains($contents, 'prefers-reduced-motion'),
                'dark' => str_contains($contents, 'light-dark(') || str_contains($contents, 'prefers-color-scheme: dark'),
                'print' => str_contains($contents, '@media print'),
                'forcedColors' => str_contains($contents, 'forced-colors'),
                'contrast' => str_contains($contents, 'prefers-contrast'),
                'containerQueries' => str_contains($contents, 'container-type') || str_contains($contents, 'container:'),
                'viewTransitions' => str_contains($contents, 'view-transition'),
            ];
        }

        return $metrics;
    }

    private function countSectionBlades(string $sectionsDirectory): int
    {
        $sectionBlades = glob($sectionsDirectory . '/*.blade.php') ?: [];

        return count($sectionBlades);
    }

    /**
     * Compares measured reality against docs/themes.json and returns a list of
     * human-readable drift notes (empty when the catalogue matches source).
     *
     * @param  array<int, array<string, mixed>>  $metrics
     * @return array<int, string>
     */
    private function catalogueDrift(string $packagesRoot, array $metrics): array
    {
        $cataloguePath = dirname($packagesRoot) . '/docs/themes.json';

        if (! is_file($cataloguePath)) {
            return ['docs/themes.json not found at ' . $cataloguePath];
        }

        $decoded = json_decode((string) file_get_contents($cataloguePath), true);

        if (! is_array($decoded) || ! isset($decoded['themes']) || ! is_array($decoded['themes'])) {
            return ['docs/themes.json is not valid or has no themes[] array.'];
        }

        $cataloguedKeys = [];

        foreach ($decoded['themes'] as $catalogueTheme) {
            if (is_array($catalogueTheme) && isset($catalogueTheme['themeKey']) && is_string($catalogueTheme['themeKey'])) {
                $cataloguedKeys[$catalogueTheme['themeKey']] = true;
            }
        }

        $drift = [];

        foreach ($metrics as $theme) {
            $slug = (string) $theme['slug'];

            if (! isset($cataloguedKeys[$slug])) {
                $drift[] = sprintf('Theme "%s" has source but no docs/themes.json entry.', $slug);
            }

            if ($theme['cssLines'] > self::CSS_LINE_BUDGET) {
                $drift[] = sprintf('Theme "%s" stylesheet is %d lines, over the %d-line budget.', $slug, $theme['cssLines'], self::CSS_LINE_BUDGET);
            }
        }

        return $drift;
    }

    /**
     * @param  array<int, array<string, mixed>>  $metrics
     * @param  array<int, string>  $catalogueDrift
     */
    private function renderReport(array $metrics, array $catalogueDrift): string
    {
        $lines = [];
        $lines[] = '# Theme catalogue report';
        $lines[] = '';
        $lines[] = 'Generated by `capell:theme-catalogue-report`. A ✓ means the theme ships that layer; a ✗ means it does not.';
        $lines[] = '';
        $lines[] = '| Theme | CSS lines | Sections | Reduced-motion | Dark | Print | Forced-colors | Contrast | Container | View-transition |';
        $lines[] = '| --- | ---: | ---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |';

        foreach ($metrics as $theme) {
            $lines[] = sprintf(
                '| %s | %d | %d | %s | %s | %s | %s | %s | %s | %s |',
                $theme['slug'],
                $theme['cssLines'],
                $theme['sections'],
                $this->tick($theme['reducedMotion']),
                $this->tick($theme['dark']),
                $this->tick($theme['print']),
                $this->tick($theme['forcedColors']),
                $this->tick($theme['contrast']),
                $this->tick($theme['containerQueries']),
                $this->tick($theme['viewTransitions']),
            );
        }

        $lines[] = '';
        $lines[] = '## Catalogue validation';
        $lines[] = '';

        if ($catalogueDrift === []) {
            $lines[] = 'No drift: every theme with source has a docs/themes.json entry and sits within the CSS budget.';
        } else {
            foreach ($catalogueDrift as $note) {
                $lines[] = '- ' . $note;
            }
        }

        $lines[] = '';

        return implode("\n", $lines) . "\n";
    }

    private function tick(bool $present): string
    {
        return $present ? '✓' : '✗';
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
