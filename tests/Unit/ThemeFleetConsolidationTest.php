<?php

declare(strict_types=1);

const THEMES_USING_SHARED_PAGE_SHELL = [
    'agency',
    'awards',
    'brutalist',
    'catalogue',
    'curated',
    'directory',
    'editorial',
    'magazine',
    'minimalist',
    'onepage',
    'photography',
    'platform',
    'portfolio',
    'saas',
    'showreel',
    'submissions',
];

it('owns the repeated child-theme page structure in one Foundation component', function (): void {
    $packagesRoot = dirname(__DIR__, 3);
    $componentPath = $packagesRoot . '/theme-foundation/resources/views/components/theme/page-shell.blade.php';
    $component = file_get_contents($componentPath);

    expect($component)->toBeString()
        ->toContain('themePrefix')
        ->toContain('tokens()')
        ->toContain('chromeHeader')
        ->toContain('mainContent')
        ->toContain('chromeFooter')
        ->toContain('<main id="main-content">');

    foreach (THEMES_USING_SHARED_PAGE_SHELL as $themeKey) {
        $pageView = file_get_contents($packagesRoot . '/theme-' . $themeKey . '/resources/views/page.blade.php');

        expect($pageView)->toBeString()
            ->toContain('<x-capell-theme-foundation::theme.page-shell')
            ->not->toContain('tokens()')
            ->not->toContain('<main');
    }
});

it('does not retain the unused livewire page stubs or health-check pins', function (): void {
    $packagesRoot = dirname(__DIR__, 3);
    $stubFiles = glob($packagesRoot . '/theme-*/resources/views/livewire/page/page.blade.php') ?: [];

    expect($stubFiles)->toBeEmpty();

    foreach (glob($packagesRoot . '/theme-*/src/Health/*HealthCheck.php') ?: [] as $healthCheckFile) {
        $source = file_get_contents($healthCheckFile);

        expect($source)->toBeString()
            ->not->toContain('resources/views/livewire/page/page.blade.php');
    }
});

it('enables split theme css by default', function (): void {
    $configuration = require dirname(__DIR__, 2) . '/config/capell-theme-foundation.php';

    expect(data_get($configuration, 'tailwind.split_theme_css'))->toBeTrue();
});
