<?php

declare(strict_types=1);

test('dark-mode themes pin readable tokens inside the dark media query', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $expectations = [
        'theme-agency/resources/css/theme-agency.css' => ['--ppc-ink: oklch(95.5% 0.03 85);', '--ppc-paper: oklch(19% 0.01 40);'],
        'theme-awards/resources/css/theme-awards.css' => ['--sbs-ink: oklch(94% 0.03 235);', '--sbs-paper: oklch(19% 0.04 255);'],
        'theme-catalogue/resources/css/theme-catalogue.css' => ['--fga-ink: oklch(97.2% 0.003 247.9);'],
        'theme-editorial/resources/css/theme-editorial.css' => ['--dnews-ink: oklch(94.8% 0.0131 82.4);', '--dnews-paper: oklch(16.5% 0.007 78.1);'],
        'theme-magazine/resources/css/theme-magazine.css' => ['--gcm-ink: oklch(91.37% 0.0198 87.52);', '--gcm-paper: oklch(22.6% 0.0341 270.96);'],
        'theme-minimalist/resources/css/theme-minimalist.css' => ['--qwg-ink: oklch(0.948 0.011 76.6);', '--qwg-paper: oklch(0.292 0.008 59.4);'],
        'theme-onepage/resources/css/theme-onepage.css' => ['--ops-ink: oklch(94% 0.02 70);', '--ops-paper: oklch(20% 0.015 60);'],
        'theme-photography/resources/css/theme-photography.css' => ['--dlm-ink: oklch(0.926 0.017 84.588);', '--dlm-paper: oklch(0.193 0.011 80.515);'],
        'theme-portfolio/resources/css/theme-portfolio.css' => ['--csp-ink: oklch(0.96 0.02 95);', '--csp-paper: oklch(0.17 0.015 75);'],
        'theme-saas/resources/css/theme-saas.css' => ['--lga-ink: oklch(96.83% 0.0069 247.9);', '--lga-paper: oklch(18.31% 0.0309 263.38);'],
    ];

    foreach ($expectations as $relativePath => $tokens) {
        $css = file_get_contents($packagesPath . '/' . $relativePath);

        expect($css)->toContain('@media (prefers-color-scheme: dark)')
            ->and($css)->toContain('color-scheme: dark;');

        foreach ($tokens as $token) {
            expect($css)->toContain($token);
        }
    }
});
