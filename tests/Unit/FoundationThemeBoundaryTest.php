<?php

declare(strict_types=1);

it('owns the opinionated public body behavior', function (): void {
    $body = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/body.blade.php');

    expect($body)->toContain('showLightbox');
});

it('renders hero content and links through public safety boundaries', function (): void {
    $hero = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/hero.blade.php');

    expect($hero)
        ->toContain('<x-capell::content')
        ->toContain('PublicUrlSanitizer::sanitize')
        ->not->toContain('strip_tags(')
        ->not->toContain('{!!');
});

it('owns the opinionated content prose and divider behavior', function (): void {
    $content = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/content.blade.php');

    expect($content)->toContain('data-lightbox');
});

it('owns the foundation frontend javascript runtime', function (): void {
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/resources/js/capell-frontend.js');
    $config = file_get_contents(dirname(__DIR__, 2) . '/config/capell-theme-foundation.php');

    expect($entrypoint)->toContain('@ryangjchandler/alpine-tooltip')
        ->and($entrypoint)->toContain('@awcodes/alpine-floating-ui')
        ->and($entrypoint)->toContain('./utilities/lightbox')
        ->and($config)->toContain('@ryangjchandler/alpine-tooltip')
        ->and($config)->toContain('@awcodes/alpine-floating-ui');
});

it('bundles layout builder javascript into the foundation frontend runtime', function (): void {
    $contributor = file_get_contents(dirname(__DIR__, 2) . '/src/Support/Assets/FoundationThemeAssetContributor.php');
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/resources/js/capell-frontend.js');

    expect($entrypoint)->toContain('./widgets/widget/carousel')
        ->and($contributor)->toContain("new ViteResourceSourceData('resources/js/capell-frontend.js', 'vendor/capell-theme-foundation')")
        ->and($contributor)->toContain('theme-foundation:runtime')
        ->and($contributor)->not->toContain('LAYOUT_BUILDER_ASSETS_CONDITION');
});

it('moves modern widget interactions out of blade and into the frontend runtime', function (): void {
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/resources/js/capell-frontend.js');
    $faq = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/modern/faq-section.blade.php');
    $pricing = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/modern/pricing-table.blade.php');
    $testimonials = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/modern/testimonials.blade.php');
    $widgetsCss = file_get_contents(dirname(__DIR__, 2) . '/resources/css/widgets/foundation-widgets.css');

    expect($entrypoint)
        ->toContain('data-faq-category-tab')
        ->toContain('data-billing-toggle')
        ->toContain('data-carousel-direction')
        ->toContain('data-carousel-slide')
        ->toContain('data-theme-pathways')
        ->toContain('data-pathway-panel')
        ->toContain('data-theme-spotlight')
        ->toContain('data-spotlight-tab')
        ->and($widgetsCss)->toContain('@keyframes faqFadeIn')
        ->and($faq)->not->toContain('<style')
        ->and($faq)->not->toContain('<script')
        ->and($faq)->not->toContain('onclick=')
        ->and($pricing)->not->toContain('<script')
        ->and($pricing)->not->toContain('onclick=')
        ->and($testimonials)->not->toContain('<script')
        ->and($testimonials)->not->toContain('onclick=');
});

it('keeps public asset widget blade on preloaded relations', function (): void {
    $viewDirectory = dirname(__DIR__, 2) . '/resources/views/components/widget';
    $viewFiles = [
        $viewDirectory . '/asset/pages.blade.php',
        $viewDirectory . '/modern/faq-section.blade.php',
        $viewDirectory . '/modern/feature-list.blade.php',
        $viewDirectory . '/modern/pricing-table.blade.php',
        $viewDirectory . '/modern/process-steps.blade.php',
        $viewDirectory . '/modern/stats-section.blade.php',
        $viewDirectory . '/modern/testimonials.blade.php',
    ];

    foreach ($viewFiles as $viewFile) {
        $view = file_get_contents($viewFile);

        throw_unless(is_string($view), RuntimeException::class, sprintf('Expected %s to be readable.', $viewFile));

        expect($view)
            ->not->toContain('loadParent(')
            ->not->toContain('$widgetAsset->asset->translation')
            ->not->toContain('$widgetAsset->asset->getMeta')
            ->not->toContain('$asset->translation?->');
    }
});

it('publishes the foundation frontend runtime build during setup', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');
    $action = file_get_contents(dirname(__DIR__, 2) . '/src/Actions/SetupFoundationThemePackageAction.php');
    $manifestJson = file_get_contents(dirname(__DIR__, 2) . '/publishes/build/manifest.json');

    if (! is_string($manifestJson)) {
        throw new RuntimeException('Unable to read the Foundation build manifest.');
    }

    $manifest = json_decode(
        $manifestJson,
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($manifest)) {
        throw new RuntimeException('Expected the Foundation build manifest to decode to an object.');
    }

    $runtimeEntry = $manifest['resources/js/capell-frontend.js'] ?? null;
    $runtimeFile = is_array($runtimeEntry) ? ($runtimeEntry['file'] ?? null) : null;

    throw_unless(is_string($runtimeFile), RuntimeException::class, 'Expected a Foundation frontend runtime asset.');

    $runtime = file_get_contents(dirname(__DIR__, 2) . '/publishes/build/' . $runtimeFile);

    expect($provider)->toContain('capell-theme-foundation-assets')
        ->and(file_exists(dirname(__DIR__, 2) . '/publishes/build/manifest.json'))->toBeTrue()
        ->and($runtime)->toBeString()
        ->toContain('tabs-change', 'alpine:init', '_x_dataStack')
        ->and($action)->toContain('vendor:publish')
        ->and($action)->toContain('capell-theme-foundation-assets');
});

it('owns the default body content and layout component files', function (): void {
    $layout = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/layout/index.blade.php');

    throw_unless(is_string($layout), RuntimeException::class, 'Expected foundation layout Blade file to be readable.');

    expect(file_exists(dirname(__DIR__, 2) . '/resources/views/components/app/body.blade.php'))->toBeTrue()
        ->and(file_exists(dirname(__DIR__, 2) . '/resources/views/components/content.blade.php'))->toBeTrue()
        ->and(file_exists(dirname(__DIR__, 2) . '/resources/views/components/layout/index.blade.php'))->toBeTrue()
        ->and($layout)->toContain('$themeMeta = is_array($theme?->meta ?? null) ? $theme->meta : []')
        ->and($layout)->not->toContain('$theme[\'meta\']')
        ->and($layout)->toContain('$layoutMeta = is_array($layout?->meta ?? null) ? $layout->meta : []')
        ->and($layout)->toContain("\$header ??= array_key_exists('header', \$layoutMeta) ? \$layoutMeta['header'] : null")
        ->and($layout)->toContain("\$footer ??= array_key_exists('footer', \$layoutMeta) ? \$layoutMeta['footer'] : null")
        ->and($layout)->toContain('<x-capell::header.index />')
        ->and($layout)->toContain('<x-capell::layout.main')
        ->and($layout)->toContain("\$themeMeta['footer_file'] ?? 'capell::footer'");

    expect($layout)
        ->not->toContain('$isSystemPageLayout &&')
        ->not->toContain('system_page_layout')
        ->not->toContain('background: #f8fafc')
        ->not->toContain('components.demo.contact-page');
});

it('provides the hero component registered by Layout Builder', function (): void {
    $heroPath = dirname(__DIR__, 2) . '/resources/views/components/widget/hero.blade.php';
    $hero = file_get_contents($heroPath);

    throw_unless(is_string($hero), RuntimeException::class, 'Expected foundation hero Blade file to be readable.');

    expect($heroPath)->toBeFile()
        ->and($hero)->toContain('call_to_action_label')
        ->and($hero)->toContain('focus-visible:outline-2')
        ->and($hero)->toContain('motion-reduce:transition-none')
        ->and($hero)->not->toContain('::query()')
        ->and($hero)->not->toContain('DB::');
});

it('gives every footer social link an accessible fallback name', function (): void {
    $socialLinks = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/footer/social-links.blade.php');

    expect($socialLinks)->toBeString()
        ->and($socialLinks)->toContain('parse_url')
        ->and($socialLinks)->toContain('aria-label="{{ $label }}"');
});

it('keeps runtime asset registrations behind the installed package guard', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');

    throw_unless(is_string($provider), RuntimeException::class, 'Expected foundation theme service provider to be readable.');

    $guardPosition = strpos($provider, 'if (! $this->isPackageInstalled())');
    $assetRegistrationPosition = strpos($provider, '$this->registerVendorCssJsAssets();');

    throw_if(! is_int($guardPosition) || ! is_int($assetRegistrationPosition), RuntimeException::class, 'Expected foundation theme asset guard and registration calls to be present.');

    expect($guardPosition)->not->toBeFalse()
        ->and($assetRegistrationPosition)->not->toBeFalse()
        ->and($guardPosition)->toBeLessThan($assetRegistrationPosition);
});

it('registers foundation chrome components for admin selection', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');

    throw_unless(is_string($provider), RuntimeException::class, 'Expected foundation theme service provider to be readable.');

    expect($provider)->toContain("registerHeader('capell::header.index'")
        ->and($provider)->toContain("registerFooter('capell::footer'");
});

it('does not rebuild tailwind assets for runtime theme color changes', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');
    $command = file_get_contents(dirname(__DIR__, 2) . '/src/Console/Commands/GenerateTailwindAssetsCommand.php');
    $generator = file_get_contents(dirname(__DIR__, 2) . '/src/Support/Tailwind/TailwindAssetsGenerator.php');
    $tokenAction = file_get_contents(dirname(__DIR__, 2) . '/src/Actions/ResolveFoundationThemeTokensAction.php');

    expect($provider)->not->toContain('ThemeColorsUpdated')
        ->and($command)->not->toContain('--theme-key')
        ->and($generator)->toContain('ResolveFoundationThemeTokensAction')
        ->and($tokenAction)->toContain('->merge($theme instanceof Theme && is_array($theme->colors) ? $theme->colors : [])');
});

it('renders the shared theme page with a matching skip link target and main landmark', function (): void {
    $page = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/page.blade.php');

    // Wave 7 landmark restructure: the token-carrying wrapper is the
    // outermost element, and <main> demotes inside it as a sibling of the
    // chrome (nav/footer) — see ThemeLandmarkStructureTest for the DOM-level
    // guard that nav/footer never nest inside <main>.
    expect($page)->toContain('href="#main-content"')
        ->and($page)->toContain('<main')
        ->and($page)->toContain('id="main-content"')
        ->and($page)->toContain('id="theme-status"')
        ->and($page)->toContain('role="status"')
        ->and($page)->toContain('aria-live="polite"')
        ->and($page)->toContain('aria-atomic="true"')
        ->and($page)->toContain('style="{{ collect($brand->tokens())')
        ->and($page)->toContain('class="site-theme-shell');
});

it('documents the stable child theme override surface', function (): void {
    $readme = file_get_contents(dirname(__DIR__, 2) . '/README.md');
    $overview = file_get_contents(dirname(__DIR__, 2) . '/docs/overview.md');
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');
    $tokens = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/head/tokens.blade.php');

    foreach ([$readme, $overview] as $document) {
        expect($document)
            ->toContain('Child Theme Override Contract')
            ->toContain("extends: 'default'")
            ->toContain('`navigation`, `hero`, `features`, `proof`, `content-listing`, `search`, `pagination`, `form`, `contact-split`, `cta`, `footer`')
            ->toContain('`capell::theme.page`')
            ->toContain('`capell::layout.area`')
            ->toContain('`capell::media.svg`')
            ->toContain('`--foundation-page-bg`')
            ->toContain('`--foundation-section-spacing`')
            ->toContain('`--foundation-widget-gap`')
            ->toContain('`header`')
            ->toContain('authoring metadata')
            ->toContain('controls');
    }

    expect($provider)
        ->toContain("includedSections: ['navigation', 'hero', 'features', 'proof', 'content-listing', 'search', 'pagination', 'form', 'contact-split', 'cta', 'footer']")
        ->toContain("->register('header'");

    expect($tokens)
        ->toContain('--foundation-page-bg')
        ->toContain('--foundation-section-spacing')
        ->toContain('--foundation-widget-gap');
});

it('delegates primary header navigation to the navigation render hook', function (): void {
    $header = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/header/index.blade.php');
    $layoutArea = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/layout/area.blade.php');
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');
    $themeStyles = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');

    expect($header)->toContain("scenario: 'theme-foundation-primary-navigation'")
        ->and($header)->toContain("target: 'capell::header.index'")
        ->and($header)->toContain('<x-capell::layout.area area="header" />')
        ->and($layoutArea)->toContain('capell-layout-builder::components.layout.area')
        ->and($provider)->toContain("->register('header'")
        ->and($header)->toContain('capell-navigation-menu-open-changed')
        ->and($header)->toContain('capell-product-header')
        ->and($header)->toContain('capell-product-nav-item')
        ->and($header)->toContain('max-xl:px-0')
        ->and($header)->toContain("getMeta('header_color')")
        ->and($header)->toContain("'var(--foundation-body-fg)'")
        ->and($header)->toContain("'var(--foundation-header-bg)'")
        ->and($header)->toContain("'var(--foundation-page-bg)'")
        ->and($header)->toContain("'header-logo h-10 max-h-10 w-auto'")
        ->and($header)->not->toContain('h-[12vh]')
        ->and($themeStyles)->toContain('@media (max-width: 1279px)')
        ->and($themeStyles)->toContain('@media (min-width: 1280px)')
        ->and($themeStyles)->toContain('.capell-product-header__brand-link:focus-visible')
        ->and($header)->not->toContain('x-ref="toggleMenu"')
        ->and($header)->not->toContain('toggleMenu()')
        ->and($header)->not->toContain('Capell\\Navigation');
});

it('ships a stable premium default footer shell', function (): void {
    $footer = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/footer/index.blade.php');
    $siteInfo = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/footer/site-info.blade.php');
    $menu = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/footer/menu.blade.php');
    $subFooter = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/footer/sub-footer.blade.php');
    $themeStyles = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');

    expect($footer)
        ->toContain('capell-product-footer')
        ->toContain('capell-product-footer__inner')
        ->toContain("resolveFooterColor('footer_background_color', '#edf2ee')")
        ->toContain("resolveFooterColor('footer_dark_background_color', '#0b1716')")
        ->toContain("__('capell-theme-foundation::generic.footer')")
        ->and($siteInfo)->toContain('max-h-14')
        ->and($siteInfo)->not->toContain('max-h-[32vh]')
        ->and($menu)->toContain('break-words')
        ->and($menu)->not->toContain('break-all')
        ->and($subFooter)->not->toContain('sm:grid-col-2')
        ->and($themeStyles)->toContain('.capell-product-footer')
        ->and($themeStyles)->toContain('.capell-product-footer a:focus-visible');
});

it('uses complete shared navigation disclosure below the wide desktop breakpoint', function (): void {
    $header = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/header/index.blade.php');
    $navigation = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/chrome/navigation.blade.php');
    $mobileNavigation = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/partials/mobile-navigation.blade.php');
    $chromeStyles = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/chrome.css');

    expect($header)
        ->not->toContain('[&_.nav-items]:lg:flex-nowrap')
        ->and($navigation)->not->toContain('<style>')
        ->and($navigation)->not->toContain('id="main-content"')
        ->and($navigation)->toContain('theme-chrome-nav__links')
        ->and($navigation)->toContain('theme-chrome-nav__mobile-cta')
        ->and($navigation)->toContain('aria-controls="theme-chrome-navigation-panel"')
        ->and($navigation)->toContain('aria-current="page"')
        ->and($navigation)->toContain('{{ $section->ctaLabel }}')
        ->and($mobileNavigation)->toContain('aria-controls="{{ $mobileMenuId }}"')
        ->and($mobileNavigation)->toContain('aria-current="page"')
        ->and($chromeStyles)->toContain('@media (min-width: 1200px)')
        ->and($chromeStyles)->toMatch('/\.theme-chrome-nav__links\s*{[^}]*white-space: nowrap;/s')
        ->and($chromeStyles)->toMatch('/\.theme-chrome-nav__mobile:not\(\[open\]\) \.theme-chrome-nav__mobile-panel\s*{\s*display: none;/')
        ->and($chromeStyles)->toContain('@media (prefers-reduced-motion: reduce)');
});

it('renders shared navigation disclosure for menu items or a complete cta', function (): void {
    $renderNavigation = static fn (array $items, ?string $ctaLabel, ?string $ctaUrl): string => view('capell-theme-foundation::theme.chrome.navigation', [
        'section' => (object) [
            'brandName' => 'Capell',
            'items' => $items,
            'ctaLabel' => $ctaLabel,
            'ctaUrl' => $ctaUrl,
        ],
    ])->render();

    $items = array_map(
        static fn (int $index): array => [
            'label' => "Item {$index}",
            'url' => "/item-{$index}",
            'active' => $index === 1,
        ],
        range(1, 7),
    );

    $itemsAndCta = $renderNavigation($items, 'Contact', '/contact');
    $ctaOnly = $renderNavigation([], 'Contact', '/contact');
    $empty = $renderNavigation([], null, null);

    expect($itemsAndCta)->toContain('theme-chrome-nav__mobile')
        ->and(substr_count($itemsAndCta, 'theme-chrome-nav__mobile-cta'))->toBe(1)
        ->and(substr_count($itemsAndCta, 'aria-current="page"'))->toBe(2)
        ->and(substr_count($itemsAndCta, 'id="theme-chrome-navigation-panel"'))->toBe(1)
        ->and($itemsAndCta)->not->toContain('id="main-content"')
        ->and($ctaOnly)->toContain('theme-chrome-nav__mobile')
        ->and($ctaOnly)->toContain('theme-chrome-nav__mobile-cta')
        ->and($ctaOnly)->toContain('href="/contact"')
        ->and($empty)->not->toContain('theme-chrome-nav__mobile')
        ->and($empty)->not->toContain('theme-chrome-nav__cta');
});

it('renders the shared child-theme mobile menu closed with complete links only', function (): void {
    $html = view('capell-theme-foundation::theme.partials.mobile-navigation', [
        'links' => [
            ['label' => 'Current page', 'url' => '/current', 'active' => true],
            ['label' => 'Missing destination'],
            ['url' => '/missing-label'],
        ],
        'ctaLabel' => 'Contact',
        'ctaUrl' => '/contact',
        'menuId' => 'primary navigation',
    ])->render();

    expect($html)
        ->toContain('class="capell-mobile-nav"')
        ->toContain('aria-controls="primary-navigation"')
        ->toContain('id="primary-navigation"')
        ->toContain('aria-current="page"')
        ->toContain('href="/current"')
        ->toContain('href="/contact"')
        ->not->toContain('open>')
        ->not->toContain('Missing destination')
        ->not->toContain('/missing-label');
});

it('delegates main layout container rendering to the shared frontend hook', function (): void {
    $main = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/layout/main.blade.php');

    expect($main)->toContain('RenderHookLocation::MainContent')
        ->and($main)->toContain("scenario: 'frontend-main-layout'")
        ->and($main)->toContain("target: 'capell::layout.main'")
        ->and($main)->toContain('$mainContentHookOutput !==')
        ->and($main)->toContain('<x-capell::content')
        ->and($main)->not->toContain('Capell\\LayoutBuilder')
        ->and($main)->not->toContain('LayoutWidgetData')
        ->and($main)->not->toContain('CapellLayoutManager')
        ->and($main)->not->toContain('x-capell::layout.container');
});

it('owns the product showcase styling for modern homepage widgets', function (): void {
    $hero = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/modern/hero-banner.blade.php');
    $cardGrid = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/modern/card-grid.blade.php');
    $featureList = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/modern/feature-list.blade.php');
    $cta = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/modern/cta-section.blade.php');
    $gallery = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/widget/modern/image-gallery.blade.php');

    expect($hero)->toContain('hero_panel_title')
        ->and($hero)->toContain('hero_empty_title')
        ->and($cardGrid)->toContain('ap-card__link')
        ->and($featureList)->toContain('ap-feature-item__icon')
        ->and($cta)->toContain('Homepage content is widget, media, and layout driven.')
        ->and($gallery)->toContain('ap-gallery-caption');
});

it('does not own premium demo kit homepage section styling', function (): void {
    $themeCss = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');

    expect($themeCss)->not->toContain('capell-widget-homepage-section');
});
