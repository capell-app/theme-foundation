<?php

declare(strict_types=1);

/*
 * Phase 3 differentiation guard. Premium themes must not be colour-swaps of one
 * another, so no two theme packages may ship a byte-identical primary
 * stylesheet. This test caught the magazine trio (dense-news-analysis,
 * design-led-magazine, global-culture-magazine) shipping the same CSS and
 * prevents that class of regression from returning.
 */

/**
 * @return array<string, string> packageDirectory => stylesheet contents
 */
function themePrimaryStylesheets(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $stylesheetFiles = glob($packagesRoot . '/theme-*/resources/css/theme-*.css') ?: [];

    sort($stylesheetFiles);

    $stylesheets = [];

    foreach ($stylesheetFiles as $stylesheetFile) {
        // packages/theme-foo/resources/css/theme-foo.css -> theme-foo
        $packageDirectory = basename(dirname($stylesheetFile, 3));
        $contents = file_get_contents($stylesheetFile);

        if (is_string($contents) && trim($contents) !== '') {
            $stylesheets[$packageDirectory] = $contents;
        }
    }

    return $stylesheets;
}

it('ships a distinct primary stylesheet for every theme', function (): void {
    $stylesheets = themePrimaryStylesheets();

    expect(count($stylesheets))->toBeGreaterThan(1);

    $seenByHash = [];

    foreach ($stylesheets as $packageDirectory => $contents) {
        $hash = md5($contents);

        expect($seenByHash)->not->toHaveKey(
            $hash,
            sprintf(
                '%s ships a byte-identical stylesheet to %s — premium themes must differ by more than colour.',
                $packageDirectory,
                $seenByHash[$hash] ?? '',
            ),
        );

        $seenByHash[$hash] = $packageDirectory;
    }
});
