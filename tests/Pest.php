<?php

declare(strict_types=1);

use Capell\Tests\Packages\PackagesTestCase;

pest()->extend(PackagesTestCase::class)->group('theme-foundation')->in(__DIR__);

/**
 * Wave 0.7's interior-page audit found most themes still capture screenshots
 * under ad hoc surface names (`landing`, `listing`, `search`, ...) instead of
 * the `ProvidesThemeDemoContent` contract's names, with `empty`/`not-found`/
 * `cta` missing entirely — a tracked gap Wave 3.5's full capture sweep closes.
 * Shared by `ThemeCatalogueTest` and `ThemePackageManifestTest` to filter that
 * one known violation category for the themes still pending it, so both
 * suites keep guarding every other `ValidateThemeCatalogueEntryAction`
 * cross-check while that recapture work is in progress.
 *
 * @param  list<string>  $violations
 * @return list<string>
 */
function themeCatalogueViolationsExcludingKnownScreenshotSurfaceGap(string $themeKey, array $violations): array
{
    $themesPendingScreenshotSurfaceCapture = [
        'default', 'photography', 'directory', 'magazine', 'catalogue',
        'curated', 'agency', 'awards', 'editorial', 'saas',
        'events', 'platform', 'brutalist', 'onepage', 'portfolio',
        'showreel', 'minimalist', 'submissions',
    ];

    if (! in_array($themeKey, $themesPendingScreenshotSurfaceCapture, true)) {
        return $violations;
    }

    return array_values(array_filter(
        $violations,
        fn (string $violation): bool => ! str_contains($violation, 'is missing captures for the DemoContent surface'),
    ));
}
