<?php

declare(strict_types=1);

/*
 * Wave 11.4 RTL guard. The fleet's theme CSS is written almost entirely
 * with logical properties (margin-inline-*, border-inline-*, inset-inline-*,
 * text-align: start/end) rather than physical left/right ones, so it is
 * RTL-correct without any per-language override. This test bans new
 * physical inline-axis declarations from creeping back in, with a narrow,
 * named allowlist for declarations that are deliberately direction-fixed
 * rather than text-flow-relative:
 *
 * - showreel's and editorial's CSS-triangle "play" glyphs
 *   (border-left forming the wedge, plus the margin-left nudge that
 *   centres it) point right regardless of page direction, matching the
 *   universal native <video>/YouTube play-icon convention — flipping them
 *   under RTL would look like a rendering bug, not a translation.
 * - foundation's billing-toggle-dot `left: 30px` (is-annual state) is left
 *   physical pending verification of its paired "off" state, which lives
 *   in Blade/Tailwind utility classes outside this CSS file — deliberately
 *   deferred, not an oversight.
 */

function themeRtlPackagesRoot(): string
{
    return dirname(__DIR__, 4) . '/packages';
}

it('keeps theme CSS free of new physical inline-axis properties (RTL logical-property guard)', function (): void {
    $physicalPattern = '/(?<!-)\b(margin|padding|border)-(left|right)\b\s*:|(?<!-)\btext-align\s*:\s*(left|right)\b|^\s*(left|right)\s*:/m';

    $allowlist = [
        // theme-showreel & theme-editorial: CSS-triangle
        // play glyphs — direction-fixed by icon convention, not text flow.
        'border-left: 0.85rem solid var(--mva-ink);',
        'margin-left: 0.25rem;',
        'border-left: 0.62rem solid #f6f3ec;',
        'margin-left: 0.12rem;',
        // theme-foundation: billing-toggle-dot's paired "off" state lives
        // in Blade/Tailwind utilities outside this CSS file; deferred
        // pending that verification, not an oversight.
        'left: 30px;',
    ];

    $root = themeRtlPackagesRoot();
    $cssFiles = [];

    foreach (glob($root . '/theme-*/resources/css', GLOB_ONLYDIR) ?: [] as $cssDirectory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cssDirectory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'css') {
                $cssFiles[] = $file->getPathname();
            }
        }
    }

    $cssFiles = array_values(array_unique($cssFiles));

    expect($cssFiles)->not->toBeEmpty('Theme CSS discovery must find at least one file.');

    foreach ($cssFiles as $cssFile) {
        $lines = file($cssFile) ?: [];

        foreach ($lines as $lineNumber => $line) {
            if (preg_match($physicalPattern, $line) !== 1) {
                continue;
            }

            $trimmed = trim($line);

            if (in_array($trimmed, $allowlist, true)) {
                continue;
            }

            $relative = str_replace($root . '/', '', $cssFile);

            expect(false)->toBeTrue(
                "{$relative}:" . ($lineNumber + 1) . " uses a physical property ('{$trimmed}') — use the logical equivalent (margin/padding/border-inline-start|end, inset-inline-start|end, text-align: start|end) or add it to this test's explicit, commented allowlist if it is deliberately direction-fixed.",
            );
        }
    }
});
