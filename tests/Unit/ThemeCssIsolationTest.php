<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../tests/Packages/Support/ThemeLayoutNativeSupport.php';

/*
 * Phase C: a theme converted to render through x-capell::layout +
 * layout-builder (see themesConvertedToLayoutBuilder()) no longer ships a
 * theme-<key>.css stylesheet under this glob, so themeIsolationStylesheets()
 * below simply has nothing to check for it — its absence is acceptable for
 * converted themes only, and every currently-shipped stylesheet is still
 * checked exactly as strictly as before.
 *
 * Guards the CSS-isolation contract that keeps themes from styling each other.
 *
 * All theme stylesheets compile into a single frontend bundle, so a theme must
 * scope its rules under a class unique to that theme. Two collision patterns
 * are banned:
 *   - the historic shared `.editorial-shell` / `.editorial-*` namespace, and
 *   - hanging theme-specific rules on `.site-theme-shell`, the shared Foundation
 *     base shell class (only Foundation may style it).
 *
 * A child theme may still *reference* the shared base class in its Blade; it
 * just may not define rules on it.
 */

/**
 * @return array<string, string> packageDirectory => primary stylesheet contents
 */
function themeIsolationStylesheets(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $stylesheetFiles = glob($packagesRoot . '/theme-*/resources/css/theme-*.css') ?: [];

    sort($stylesheetFiles);

    $stylesheets = [];

    foreach ($stylesheetFiles as $stylesheetFile) {
        // packages/theme-foo/resources/css/theme-foo.css -> theme-foo
        $packageDirectory = basename(dirname($stylesheetFile, 3));

        // Foundation owns the shared base classes; the guard is for child themes.
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

it('never reintroduces the shared .editorial-shell collision namespace', function (): void {
    foreach (themeIsolationStylesheets() as $packageDirectory => $contents) {
        expect(str_contains($contents, '.editorial-shell'))
            ->toBeFalse("{$packageDirectory} must scope its CSS under a unique class, not the shared .editorial-shell namespace.");
    }
});

it('does not style the shared Foundation base shell class', function (): void {
    foreach (themeIsolationStylesheets() as $packageDirectory => $contents) {
        // A rule block on the shared base class leaks onto every theme that
        // carries it. Match the selector at the start of a rule (followed by
        // a combinator, comma, or opening brace) rather than incidental text.
        $stylesTheSharedShell = (bool) preg_match('/\.site-theme-shell\s*[{,\s]/', $contents)
            && (bool) preg_match('/\.site-theme-shell\b[^;{}]*\{/', $contents);

        expect($stylesTheSharedShell)
            ->toBeFalse("{$packageDirectory} must not define rules on the shared .site-theme-shell base class; scope them under a theme-unique class.");
    }
});

/*
 * Wave 6.7 budget guard: per docs/creating-a-theme.md, new/refactored sections
 * should reach for Tailwind utilities for generic layout/spacing and reserve
 * prefixed CSS for signature styling (tokens, ornaments, states, motion), so
 * bespoke per-theme CSS can't silently balloon. The ceiling below was
 * recalibrated after Waves 1-3 legitimately grew every theme's stylesheet
 * (reduced-motion guards, focus rings, print stylesheets, dark counterparts,
 * container queries, empty states) and again after Waves 6.5/11.5 added
 * cross-document View Transitions, forced-colors focus survival, and
 * prefers-contrast steps to every theme — all "states/motion" signature CSS
 * the policy expects to be bespoke. Recalibrated again for the 2026-H2 Wave
 * 4a–4c/5–7 level-up programme: each theme's Part 2 headline mechanic and
 * 4–6 signature widgets are, by definition, bespoke per-theme CSS (tokens,
 * deterministic layout seeds, scroll-driven reveals, :has()-based sync
 * states) — exactly what this policy is meant to make room for, not the
 * runaway growth it exists to catch. The final commercial pass established
 * 2,100 lines as the fleet ceiling: it accommodates Awards' scoreboard,
 * Showreel's archive, Submissions' index, and Brutalist's zine mechanics while
 * retaining useful headroom of fewer than 80 lines above the largest theme.
 */
it('keeps each theme stylesheet under the per-theme CSS line budget', function (): void {
    $budget = 2100;

    foreach (themeIsolationStylesheets() as $packageDirectory => $contents) {
        $lineCount = substr_count($contents, "\n") + 1;

        expect($lineCount)->toBeLessThanOrEqual(
            $budget,
            "{$packageDirectory}'s stylesheet is {$lineCount} lines, over the {$budget}-line budget — prefer Tailwind utilities for generic layout/spacing (see docs/creating-a-theme.md) and reserve bespoke CSS for signature styling.",
        );
    }
});
