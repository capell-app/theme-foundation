<?php

declare(strict_types=1);

test('dark-mode themes pin readable tokens inside the dark media query', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $expectations = [
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

test('agency and business themes use the class-driven dark-mode contract', function (): void {
    $packagesPath = dirname(__DIR__, 3);
    $themes = [
        'theme-agency/resources/css/theme-agency.css' => [
            'selector' => '.ppc-shell',
            'bindings' => ['--theme-foreground', '--theme-accent', '--theme-surface'],
            'darkBindings' => ['--theme-foreground', '--theme-accent', '--theme-surface'],
        ],
        'theme-business/resources/css/theme-business.css' => [
            'selector' => '.rco-shell',
            'bindings' => [],
            'darkBindings' => ['--rco-ink', '--rco-surface', '--rco-panel'],
        ],
    ];

    foreach ($themes as $relativePath => $theme) {
        $css = (string) file_get_contents($packagesPath . '/' . $relativePath);

        foreach ($theme['bindings'] as $binding) {
            expect($css)->toContain($binding);
        }

        $selector = preg_quote('.dark ' . $theme['selector'], '/');
        $matchCount = preg_match(
            '/' . $selector . ' \{(?<body>.*?)\}/s',
            $css,
            $matches,
        );

        $darkRuleBody = preg_replace('/\s+/', ' ', trim((string) ($matches['body'] ?? '')));

        expect($matchCount)->toBe(1)
            ->and($darkRuleBody)->toContain('color-scheme: dark;')
            ->and($css)->not->toContain('@media (prefers-color-scheme: dark)');

        foreach ($theme['darkBindings'] as $binding) {
            expect($darkRuleBody)->toContain($binding);
        }
    }
});

test('theme screenshot declarations match their implemented colour schemes', function (): void {
    $packagesPath = dirname(__DIR__, 3);
    $expectedSchemes = [
        'theme-blog' => ['light', 'dark'],
        'theme-brutalist' => ['light', 'dark'],
        'theme-curated' => ['light', 'dark'],
        'theme-platform' => ['dark'],
    ];

    foreach ($expectedSchemes as $packageDirectory => $schemes) {
        $manifestPath = $packagesPath . '/' . $packageDirectory . '/docs/screenshots.json';
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $entries = is_array($manifest) && is_array($manifest['entries'] ?? null)
            ? $manifest['entries']
            : [];

        expect($entries)->not->toBeEmpty();

        foreach ($entries as $entry) {
            $entryId = is_array($entry) && is_string($entry['id'] ?? null)
                ? $entry['id']
                : 'unknown';

            expect(is_array($entry) ? ($entry['colorSchemes'] ?? null) : null)->toBe(
                $schemes,
                sprintf('%s entry %s does not declare its implemented colour schemes.', $packageDirectory, $entryId),
            );
        }
    }
});
