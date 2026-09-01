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
    'platform',
    'portfolio',
    'saas',
    'showreel',
    'submissions',
];

const THEME_FOOTER_CHROME_VIEWS = [
    'broadsheet' => 'footer.blade.php',
    'business' => 'components/business-footer.blade.php',
    'events' => 'footer.blade.php',
    'grounds' => 'footer.blade.php',
    'knowledge' => 'footer.blade.php',
    'liquid-glass' => 'footer.blade.php',
    'paperdesk' => 'components/paperdesk-footer.blade.php',
    'platform' => 'footer.blade.php',
    'switchboard' => 'footer.blade.php',
];

const THEME_LEGACY_FOOTER_SECTION_VIEWS = [
    'agency' => 'sections/footer.blade.php',
    'almanac' => 'sections/footer.blade.php',
    'awards' => 'sections/footer.blade.php',
    'bistro' => 'sections/footer.blade.php',
    'blog' => 'sections/footer.blade.php',
    'brutalist' => 'sections/footer.blade.php',
    'catalogue' => 'sections/footer.blade.php',
    'curated' => 'sections/footer.blade.php',
    'directory' => 'sections/footer.blade.php',
    'editorial' => 'sections/footer.blade.php',
    'magazine' => 'sections/footer.blade.php',
    'minimalist' => 'sections/footer.blade.php',
    'onepage' => 'sections/footer.blade.php',
    'portfolio' => 'sections/footer.blade.php',
    'saas' => 'sections/footer.blade.php',
    'showreel' => 'sections/footer.blade.php',
    'submissions' => 'sections/footer.blade.php',
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

it('renders the footer layout area through every active footer seam', function (): void {
    $packagesRoot = dirname(__DIR__, 3);
    $foundationFooter = file_get_contents($packagesRoot . '/theme-foundation/resources/views/components/footer/index.blade.php');

    expect($foundationFooter)
        ->toBeString()
        ->toContain('<x-capell::layout.area area="footer" />');

    foreach (THEME_FOOTER_CHROME_VIEWS as $themeKey => $footerView) {
        $footer = file_get_contents($packagesRoot . '/theme-' . $themeKey . '/resources/views/' . $footerView);

        expect($footer)
            ->toBeString()
            ->toContain('<x-capell::layout.area')
            ->toContain('area="footer"');
    }

    foreach (THEME_LEGACY_FOOTER_SECTION_VIEWS as $themeKey => $footerView) {
        $footer = file_get_contents($packagesRoot . '/theme-' . $themeKey . '/resources/views/' . $footerView);

        expect($footer)
            ->toBeString()
            ->toContain('<x-capell::layout.area area="footer" />');
    }
});

it('enables split theme css by default', function (): void {
    $configuration = require dirname(__DIR__, 2) . '/config/capell-theme-foundation.php';

    expect(data_get($configuration, 'tailwind.split_theme_css'))->toBeTrue();
});
