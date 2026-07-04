<?php

declare(strict_types=1);

/*
 * Guards Wave 10.2's token-consumption contract: a preset value an admin
 * edits in Theme Studio must actually change something on the rendered
 * page. An earlier fleet-wide audit found six brand tokens — spacing,
 * cardStyle, layoutPresentation, headingScale, cardDensity, and
 * overlayTreatment — declared in every theme's presets but referenced by
 * zero of the 20 child themes' own CSS, meaning the matching Theme Studio
 * controls were silent no-ops.
 *
 * This test is scoped to those six previously-dead tokens, not the full
 * preset vocabulary. navigationStyle, motionIntensity, and mediaTreatment
 * are a separate, earlier-flagged gap (still 0/20) and primaryColor /
 * neutralColor consumption gaps are tracked separately too — folding them
 * in here would make this guard fail for reasons this wave doesn't touch.
 * Widen the $tokens list below only once a wave actually wires the token.
 *
 * Detection is a static regex, same style as ThemeCssIsolationTest: a
 * token counts as "consumed" if its custom-property name appears literally
 * in the theme's own stylesheet (typically inside an
 * `@container <prefix>-tokens style(--theme-x: value)` rule) or in one of
 * Foundation's shared stylesheets.
 */

/**
 * @return array<string, string> packageDirectory => primary stylesheet contents
 */
function tokenConsumptionThemeStylesheets(): array
{
    $packagesRoot = dirname(__DIR__, 3);
    $stylesheetFiles = glob($packagesRoot . '/theme-*/resources/css/theme-*.css') ?: [];

    sort($stylesheetFiles);

    $stylesheets = [];

    foreach ($stylesheetFiles as $stylesheetFile) {
        // packages/theme-foo/resources/css/theme-foo.css -> theme-foo
        $packageDirectory = basename(dirname($stylesheetFile, 3));

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

function tokenConsumptionFoundationStylesheet(): string
{
    $packagesRoot = dirname(__DIR__, 3);
    $stylesheetFiles = glob($packagesRoot . '/theme-foundation/resources/css/**/*.css') ?: [];
    $stylesheetFiles = array_merge($stylesheetFiles, glob($packagesRoot . '/theme-foundation/resources/css/*.css') ?: []);

    $combined = '';

    foreach ($stylesheetFiles as $stylesheetFile) {
        $contents = file_get_contents($stylesheetFile);

        if (is_string($contents)) {
            $combined .= $contents;
        }
    }

    return $combined;
}

it('consumes every Wave 10.2 brand token in each theme\'s own CSS or shared Foundation CSS', function (): void {
    $tokens = [
        '--theme-spacing',
        '--theme-card-style',
        '--theme-layout-presentation',
        '--theme-heading-scale',
        '--theme-card-density',
        '--theme-overlay-treatment',
    ];

    $foundationStylesheet = tokenConsumptionFoundationStylesheet();

    foreach (tokenConsumptionThemeStylesheets() as $packageDirectory => $contents) {
        foreach ($tokens as $token) {
            $consumed = str_contains($contents, $token) || str_contains($foundationStylesheet, $token);

            expect($consumed)->toBeTrue(
                "{$packageDirectory} declares the {$token} preset value but never references it in its own CSS or Foundation's shared CSS — the matching Theme Studio control is a silent no-op.",
            );
        }
    }
});
