<?php

declare(strict_types=1);

require_once __DIR__ . '/../Support/DomQueries.php';
require_once __DIR__ . '/../Support/PublicChromeFixture.php';

use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\FoundationTheme\Tests\Support\FooterContentFixture;

use function Capell\FoundationTheme\Tests\Support\preparePublicChrome;

use Capell\Frontend\Facades\Frontend;
use Capell\ThemeEvents\Data\EventsChromeData;
use Capell\ThemeEvents\Data\EventsFooterData;
use Capell\ThemeEvents\Data\EventsNavigationData;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Blaze\Blaze;

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
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    try {
        foreach (THEMES_USING_SHARED_PAGE_SHELL as $themeKey) {
            view()->addNamespace('capell-theme-' . $themeKey, dirname(__DIR__, 3) . '/theme-' . $themeKey . '/resources/views');
            $html = view()->file(dirname(__DIR__, 3) . '/theme-' . $themeKey . '/resources/views/page.blade.php', [
                'brand' => new BrandProfileData,
                'chromeHeader' => '<header>Header landmark</header>',
                'chromeFooter' => '<footer>Footer landmark</footer>',
                'mainContent' => '<h1>Page title</h1>', 'content' => '<h1>Page title</h1>',
            ])->render();
            expect(domQueryCount($html, '//a[@href="#main-content"]'))->toBe(1, $themeKey)
                ->and(domQueryCount($html, '//main[@id="main-content"]//h1[normalize-space(.)="Page title"]'))->toBe(1)
                ->and(domQueryCount($html, '//main/preceding-sibling::header'))->toBe(1)
                ->and(domQueryCount($html, '//main/following-sibling::footer'))->toBe(1)
                ->and($html)->toContain('--theme-surface:');
        }
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
});

it('ships only current theme views and reports healthy view diagnostics', function (): void {
    $packagesRoot = dirname(__DIR__, 3);
    expect(glob($packagesRoot . '/theme-*/resources/views/livewire/page/page.blade.php') ?: [])->toBeEmpty();
    $checked = 0;
    foreach (glob($packagesRoot . '/theme-*/capell.json') ?: [] as $manifestPath) {
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $healthChecks = data_get($manifest, 'healthChecks', []);
        if (! is_array($healthChecks)) {
            throw new RuntimeException('Expected theme health declarations.');
        }
        foreach ($healthChecks as $declaration) {
            $class = data_get($declaration, 'class');
            if (! is_string($class) || ! class_exists($class)) {
                throw new RuntimeException('Expected a discoverable theme health check.');
            }
            $check = new $class;
            foreach (['requiredViewsCheck', 'requiredFilesCheck', 'themeViewsCheck', 'publicAssetsCheck'] as $method) {
                if (! is_callable([$check, $method])) {
                    continue;
                }
                $result = $check->{$method}();
                if (! $result instanceof DoctorCheckResultData) {
                    throw new RuntimeException('Expected a public diagnostic result.');
                }
                expect($result->passed)->toBeTrue($result->message)
                    ->and($result->remediation)->toBeNull();
                $checked++;
                break;
            }
        }
    }
    expect($checked)->toBeGreaterThanOrEqual(23);
});

it('renders installed content through every active footer seam', function (): void {
    $packagesRoot = dirname(__DIR__, 3);
    $site = preparePublicChrome(footerWidgets: true);
    $layout = Frontend::layout();
    Blade::component(FooterContentFixture::class, 'capell::layout.area');
    Blade::componentNamespace('Capell\\ThemePaperdesk\\View\\Components', 'capell-theme-paperdesk');
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    try {
        expect(Blade::render('<x-capell::footer.index />'))->toContain('Installed footer widget content');
        foreach ([...THEME_LEGACY_FOOTER_SECTION_VIEWS, ...THEME_FOOTER_CHROME_VIEWS] as $themeKey => $footerView) {
            $viewsPath = $packagesRoot . '/theme-' . $themeKey . '/resources/views';
            view()->addNamespace('capell-theme-' . $themeKey, $viewsPath);
            Blade::anonymousComponentPath($viewsPath . '/components', 'capell-theme-' . $themeKey);
            app('translator')->addNamespace('capell-theme-' . $themeKey, $packagesRoot . '/theme-' . $themeKey . '/resources/lang');
            $html = view()->file($viewsPath . '/' . $footerView, [
                'brand' => in_array($themeKey, ['business', 'paperdesk'], true) ? 'Public site' : new BrandProfileData,
                'summary' => 'Footer summary', 'hasFooterWidgets' => true, 'layout' => $layout,
                'chrome' => new EventsChromeData($layout, false, true, new EventsNavigationData('Public site', '/', [], null, null), new EventsFooterData('Public site', 'Footer summary', [])),
                'section' => ['columns' => [], 'links' => [], 'summary' => 'Footer summary'],
                'site' => $site, 'footerWidgets' => [['key' => 'footer-content']],
                'attributes' => new ComponentAttributeBag,
            ])->render();
            expect($html)->toContain('Installed footer widget content');
        }
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
});

it('enables split theme css by default', function (): void {
    $configuration = require dirname(__DIR__, 2) . '/config/capell-theme-foundation.php';

    expect(data_get($configuration, 'tailwind.split_theme_css'))->toBeTrue();
});
