<?php

declare(strict_types=1);

function responsiveRobustnessStylesheet(string $theme): string
{
    $packagesRoot = dirname(__DIR__, 3);
    $path = $packagesRoot . "/{$theme}/resources/css/{$theme}.css";

    if ($theme === 'theme-foundation') {
        $path = $packagesRoot . '/theme-foundation/resources/css/theme/theme.css';
    }

    $contents = file_get_contents($path);

    throw_unless(is_string($contents), RuntimeException::class, "Expected stylesheet at [{$path}].");

    return $contents;
}

it('protects every theme shell from unbroken submitted text', function (): void {
    $foundation = responsiveRobustnessStylesheet('theme-foundation');

    expect($foundation)->toMatch(
        '/\.site-theme-shell\s*\{[^}]*overflow-wrap:\s*break-word;/s',
    );
});

it('uses dynamic viewport units for mobile-sensitive fullscreen surfaces', function (): void {
    $folio = responsiveRobustnessStylesheet('theme-folio');
    $curated = responsiveRobustnessStylesheet('theme-curated');

    expect($folio)->toMatch(
        '/\.flo-shell\s*\{[^}]*min-height:\s*100dvh;/s',
    )->and($curated)->toMatch(
        '/\.mcf-lightbox-media-minimal\s*\{[^}]*max-height:\s*100dvh;/s',
    );
});

it('collapses the cited fixed multi-column layouts at their existing breakpoints', function (): void {
    $folio = responsiveRobustnessStylesheet('theme-folio');
    $almanac = responsiveRobustnessStylesheet('theme-almanac');

    expect($folio)->toMatch(
        '/@media\s*\(max-width:\s*48rem\)\s*\{.*?\.flo-double-truck,\s*\.flo-plate-grid,\s*\.flo-imprint-row,\s*\.flo-timeline-list li\s*\{[^}]*grid-template-columns:\s*1fr;/s',
    )->and($almanac)->toMatch(
        '/@media\s*\(max-width:\s*48rem\)\s*\{.*?\.alm-next-strip > ol,\s*\.alm-footer\s*\{[^}]*grid-template-columns:\s*1fr;/s',
    )->and($almanac)->toMatch(
        '/@media\s*\(max-width:\s*48rem\)\s*\{.*?\.alm-heatmap > ol\s*\{[^}]*grid-template-columns:\s*repeat\(3,\s*1fr\);/s',
    );
});
