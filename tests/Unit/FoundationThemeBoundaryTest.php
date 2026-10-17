<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\Core\Support\Themes\ThemeChromeRegistry;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\View\Components\Footer\SocialLinks;
use Capell\FoundationTheme\View\Components\Widget\Asset;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\CssDeclarations;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Capell\Tests\Support\RenderedDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Blaze\Blaze;

it('owns the opinionated public body behavior', function (): void {
    $body = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/app/body.blade.php');

    expect($body)->toContain('showLightbox');
});

it('renders hero content and links through public safety boundaries', function (): void {
    $widget = new Widget(['key' => 'hero', 'meta' => [
        'container' => 'full', 'call_to_action_label' => 'Read the guide', 'call_to_action_url' => '/guide',
    ]]);
    $widget->setRelation('blueprint', null);
    $widget->setRelation('media', collect());
    $widget->setRelation('translation', new Translation([
        'title' => 'A public hero', 'content' => '<p>Helpful <strong>copy</strong>.</p><script>alert(1)</script>',
    ]));
    $render = static fn (): string => view()->file(dirname(__DIR__, 2) . '/resources/views/components/widget/hero.blade.php', [
        'widget' => $widget, 'container' => null, 'containerKey' => 'main', 'containerWidth' => null,
        'loop' => (object) ['index' => 0],
    ])->render();
    $connection = DB::connection();
    $wasLogging = $connection->logging();
    $connection->enableQueryLog();
    $queriesBefore = count($connection->getQueryLog());
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    try {
        $document = new RenderedDocument($render());
        expect($document->elements('//h2[normalize-space(.)="A public hero"]'))->toHaveCount(1)
            ->and($document->elements('//strong[normalize-space(.)="copy"]'))->toHaveCount(1)
            ->and($document->elements('//script'))->toBeEmpty()
            ->and($document->elements('//a[@href="/guide"][normalize-space(.)="Read the guide"]'))->toHaveCount(1);
        $widget->meta = ['container' => 'full', 'call_to_action_label' => 'Read the guide', 'call_to_action_url' => 'javascript:alert(1)'];
        $hostile = new RenderedDocument($render());
        expect($hostile->elements('//a'))->toBeEmpty()
            ->and(count($connection->getQueryLog()))->toBe($queriesBefore);
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
        if (! $wasLogging) {
            $connection->disableQueryLog();
        }
    }
});
it('owns the opinionated content prose and divider behavior', function (): void {
    $content = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/content.blade.php');

    expect($content)->toContain('data-lightbox');
});

it('owns the foundation frontend javascript runtime', function (): void {
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/resources/js/capell-frontend.js');
    $config = file_get_contents(dirname(__DIR__, 2) . '/config/capell-theme-foundation.php');

    expect($entrypoint)->not->toContain('@ryangjchandler/alpine-tooltip')
        ->and($entrypoint)->not->toContain('@awcodes/alpine-floating-ui')
        ->and($entrypoint)->toContain("import('./widgets/widget/auth-menu-refresh')")
        ->and($entrypoint)->toContain("import('./utilities/lightbox')")
        ->and($entrypoint)->not->toContain("import './utilities/lightbox'")
        ->and($config)->not->toContain('@ryangjchandler/alpine-tooltip')
        ->and($config)->not->toContain('tippy.js')
        ->and($config)->not->toContain('@awcodes/alpine-floating-ui');
});

it('keeps admin tooltip dependencies out of public theme assets', function (): void {
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/resources/js/capell-frontend.js');
    $config = file_get_contents(dirname(__DIR__, 2) . '/config/capell-theme-foundation.php');
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');
    $healthCheck = file_get_contents(dirname(__DIR__, 2) . '/src/Health/FoundationThemeHealthCheck.php');

    expect($entrypoint)->not->toContain('alpine-tooltip', 'tippy.js')
        ->and($config)->not->toContain('alpine-tooltip', 'tippy.js')
        ->and($provider)->not->toContain('tippy.js')
        ->and($healthCheck)->not->toContain('tippy.js');
});

it('loads optional layout builder javascript from the foundation frontend runtime on demand', function (): void {
    $contributor = file_get_contents(dirname(__DIR__, 2) . '/src/Support/Assets/FoundationThemeAssetContributor.php');
    $entrypoint = file_get_contents(dirname(__DIR__, 2) . '/resources/js/capell-frontend.js');

    expect($entrypoint)->toContain("selector: '.swiper'")
        ->and($entrypoint)->toContain("import('./widgets/widget/carousel')")
        ->and($entrypoint)->not->toContain("import './widgets/widget/carousel'")
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
    throw_unless(is_string($widgetsCss), RuntimeException::class);

    expect($entrypoint)
        ->toContain('data-faq-category-tab')
        ->toContain('data-billing-toggle')
        ->toContain('data-carousel-direction')
        ->toContain('data-carousel-slide')
        ->toContain('data-theme-pathways')
        ->toContain('data-pathway-panel')
        ->toContain('data-theme-spotlight')
        ->toContain('data-spotlight-tab')
        ->and(collect(CssDeclarations::parse($widgetsCss))->filter(fn (array $declaration): bool => in_array('@keyframes faqFadeIn', $declaration['contexts'], true)))->not->toBeEmpty()
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
    $javascriptAssets = [];

    throw_unless(is_string($runtime), RuntimeException::class, 'Unable to read the Foundation frontend runtime asset.');

    foreach ($manifest as $asset) {
        $assetFile = is_array($asset) ? ($asset['file'] ?? null) : null;

        if (! is_string($assetFile) || ! str_ends_with($assetFile, '.js')) {
            continue;
        }

        $assetPath = dirname(__DIR__, 2) . '/publishes/build/' . $assetFile;

        throw_unless(is_file($assetPath), RuntimeException::class, sprintf('Expected built asset %s.', $assetFile));

        $javascriptAssets[] = $assetPath;
    }

    $compiledRuntime = implode('', array_map(
        static fn (string $asset): string => (string) file_get_contents($asset),
        $javascriptAssets,
    ));
    $compressedRuntime = gzencode($runtime, 9);

    throw_unless(is_string($compressedRuntime), RuntimeException::class, 'Unable to compress the Foundation frontend runtime asset.');

    expect($provider)->toContain('capell-theme-foundation-assets')
        ->and(file_exists(dirname(__DIR__, 2) . '/publishes/build/manifest.json'))->toBeTrue()
        ->and(strlen($compressedRuntime))->toBeLessThanOrEqual(5_000)
        ->and(count($javascriptAssets))->toBeGreaterThan(1)
        ->and($runtimeEntry['dynamicImports'] ?? [])->not->toBeEmpty()
        ->toContain('resources/js/widgets/widget/auth-menu-refresh.js')
        ->and($compiledRuntime)->toContain('tabs-change', 'alpine:init', '_x_dataStack')
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
        ->and($layout)->toContain("\$themeData = ['meta' => \$themeMeta];")
        ->and($layout)->not->toContain('$theme[\'meta\']')
        ->and($layout)->toContain('$layoutMeta = is_array($layout?->meta ?? null) ? $layout->meta : []')
        ->and($layout)->toContain("\$header ??= array_key_exists('header', \$layoutMeta) ? \$layoutMeta['header'] : null")
        ->and($layout)->toContain("\$footer ??= array_key_exists('footer', \$layoutMeta) ? \$layoutMeta['footer'] : null")
        ->and($layout)->toContain('<x-capell::header.index />')
        ->and($layout)->toContain('<x-capell::layout.main')
        ->and($layout)->toContain(':theme="$themeData"')
        ->and($layout)->toContain("\$themeMeta['footer_file'] ?? 'capell::footer'");

    expect($layout)
        ->not->toContain('$isSystemPageLayout &&')
        ->not->toContain('system_page_layout')
        ->not->toContain('components.demo.contact-page');
});

it('provides the hero component registered by Layout Builder', function (): void {
    $heroPath = dirname(__DIR__, 2) . '/resources/views/components/widget/hero.blade.php';
    $hero = file_get_contents($heroPath);

    throw_unless(is_string($hero), RuntimeException::class, 'Expected foundation hero Blade file to be readable.');

    expect($heroPath)->toBeFile()
        ->and($hero)->toContain('call_to_action_label')
        ->and($hero)->toContain('motion-reduce:transition-none')
        ->and($hero)->not->toContain('::query()')
        ->and($hero)->not->toContain('DB::');
});

it('gives every footer social link an accessible fallback name and drops unsafe destinations', function (): void {
    $component = new SocialLinks([
        ['url' => 'https://example.test/profile', 'title' => 'Named profile'],
        ['url' => 'https://network.test/profile', 'type' => 'network'],
        ['url' => 'https://fallback.test/profile'],
        ['url' => 'javascript:alert(1)', 'title' => 'Unsafe profile'],
    ]);
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    try {
        $html = view()->file(dirname(__DIR__, 2) . '/resources/views/components/footer/social-links.blade.php', [
            'links' => $component->links,
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
    $document = new RenderedDocument($html);
    expect($document->elements('//a'))->toHaveCount(3);
    foreach (['example.test' => 'Named profile', 'network.test' => 'network', 'fallback.test' => 'fallback.test'] as $host => $label) {
        $link = $document->element('//a[@href="https://' . $host . '/profile"]');
        expect($link->getAttribute('aria-label'))->toBe($label)
            ->and($link->getAttribute('target'))->toBe('_blank')
            ->and(explode(' ', $link->getAttribute('rel')))->toContain('noopener');
    }
});
it('renders legacy site social links only while Socials does not manage the profiles', function (): void {
    InstalledRuntimeState::run(static function (): void {
        InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true, sourceRoot: dirname(__DIR__, 2));
        $site = new Site(['name' => 'Studio', 'meta' => ['social_links' => [
            ['url' => 'https://network.test/studio', 'title' => 'Studio profile'],
        ]]]);
        $site->setRelation('siteDomain', new SiteDomain(['domain' => 'studio.test', 'scheme' => 'https']));
        Blade::component(SocialLinks::class, 'capell::footer.social-links');
        $wasBlazeEnabled = Blaze::isEnabled();
        Blaze::disable();
        $resolver = 'Capell\\Socials\\Contracts\\SocialProfilesResolver';
        try {
            $render = static fn (): string => view()->file(dirname(__DIR__, 2) . '/resources/views/components/footer/site-info.blade.php', [
                'site' => $site, 'contactPage' => null,
            ])->render();
            $legacy = new RenderedDocument($render());
            expect($legacy->elements('//a[@href="https://network.test/studio"][@aria-label="Studio profile"]'))->toHaveCount(1);

            app()->instance($resolver, new stdClass);
            $managed = new RenderedDocument($render());
            expect($managed->elements('//a[@href="https://network.test/studio"]'))->toBeEmpty();
        } finally {
            app()->forgetInstance($resolver);
            if ($wasBlazeEnabled) {
                Blaze::enable();
            }
        }
    });
});

it('keeps runtime asset registrations behind the installed package guard', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), false, sourceRoot: dirname(__DIR__, 2));
        $assets = static fn (VendorAssetEnum $type): array => CapellCore::getVendorAssetsForType($type)
            ->filter(static fn (VendorAssetData $asset): bool => $asset->packageName === FoundationThemeServiceProvider::$packageName)
            ->map(static fn (VendorAssetData $asset): string => $asset->value)
            ->sort()->values()->all();

        expect($assets(VendorAssetEnum::TailwindImport))->toBe([])
            ->and($assets(VendorAssetEnum::TailwindSource))->toBe([]);

        $package = CapellCore::getPackage(FoundationThemeServiceProvider::$packageName);
        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
        InstallPackageAction::run($package);
        $imports = [
            'resources/css/theme-foundation.css',
            'resources/css/widgets/foundation-widgets.css',
            'swiper/css',
            'swiper/css/autoplay',
            'swiper/css/navigation',
            'swiper/css/pagination',
        ];
        expect($assets(VendorAssetEnum::TailwindImport))->toBe($imports)
            ->and($assets(VendorAssetEnum::TailwindSource))->toBe(['resources/views/**/*.blade.php']);

        RefreshInstalledPackageRuntimeAction::run($package);
        RefreshInstalledPackageRuntimeAction::run($package);
        expect($assets(VendorAssetEnum::TailwindImport))->toBe($imports)
            ->and($assets(VendorAssetEnum::TailwindSource))->toBe(['resources/views/**/*.blade.php']);
    });
});

it('registers foundation chrome components for admin selection', function (): void {
    InstalledRuntimeState::run(static function (): void {
        InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('theme-foundation'), true, sourceRoot: dirname(__DIR__, 2));
        $registry = resolve(ThemeChromeRegistry::class);

        expect($registry->headerOptions())->toHaveKey('capell::header.index', __('capell-admin::form.foundation_header'))
            ->and($registry->footerOptions())->toHaveKey('capell::footer', __('capell-admin::form.foundation_footer'));
    });
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
    $brand = new BrandProfileData;
    $html = view()->file(dirname(__DIR__, 2) . '/resources/views/theme/page.blade.php', [
        'brand' => $brand, 'content' => '<h1>Public page</h1>',
        'chromeHeader' => '<header>Public navigation</header>', 'chromeFooter' => '<footer>Public footer</footer>',
    ])->render();
    $document = new RenderedDocument($html);
    $main = $document->element('//main');
    expect($main->getAttribute('id'))->toBe('main-content');
    $skip = $document->element('//a[@href="#' . $main->getAttribute('id') . '"]');
    expect(trim($skip->textContent))->toBe(__('capell-frontend::generic.skip_to_content'))
        ->and($document->elements('//main/h1[normalize-space(.)="Public page"]'))->toHaveCount(1)
        ->and($document->elements('//main/preceding-sibling::header'))->toHaveCount(1)
        ->and($document->elements('//main/following-sibling::footer'))->toHaveCount(1);
    $status = $document->element('//*[@role="status"]');
    expect($status->getAttribute('id'))->toBe('theme-status')
        ->and($status->getAttribute('aria-live'))->toBe('polite')
        ->and($status->getAttribute('aria-atomic'))->toBe('true');
    $shell = $document->element('//main/parent::div');
    expect(explode(' ', $shell->getAttribute('class')))->toContain('site-theme-shell');
    $styles = [];
    foreach (explode(';', $shell->getAttribute('style')) as $declaration) {
        [$name, $value] = explode(':', $declaration, 2);
        $styles[trim($name)] = trim($value);
    }
    expect($styles)->toMatchArray($brand->tokens());
});
it('documents the stable child theme override surface', function (): void {
    $definition = FoundationThemeServiceProvider::definition();
    $tokens = view()->file(dirname(__DIR__, 2) . '/resources/views/components/app/head/tokens.blade.php')->render();
    $documents = [
        dirname(__DIR__, 2) . '/README.md',
        dirname(__DIR__, 2) . '/docs/overview.md',
    ];

    foreach ($documents as $path) {
        $document = (string) file_get_contents($path);
        // The override documentation must name the public sections and extension surfaces customers can use.
        foreach ($definition->includedSections as $section) {
            expect($document)->toContain('`' . $section . '`');
        }
        foreach (['capell::theme.page', 'capell::layout.area', 'capell::media.svg', '--foundation-page-bg', '--foundation-section-spacing', '--foundation-widget-gap', 'header'] as $surface) {
            expect($document)->toContain('`' . $surface . '`');
        }
        expect($document)->toContain("extends: '" . $definition->key . "'")
            ->toContain('Child Theme Override Contract');
        // The public override instructions must warn against exposing editor controls or metadata.
        expect($document)->toContain('authoring metadata')->toContain('controls');
    }

    $document = new RenderedDocument($tokens);
    $style = $document->element('//style')->textContent;
    // These published custom properties are the documented child-theme override boundary.
    foreach (['--foundation-page-bg', '--foundation-section-spacing', '--foundation-widget-gap'] as $token) {
        expect($style)->toMatch('/' . preg_quote($token, '/') . '\\s*:\\s*[^;]+;/');
    }
});
it('delegates primary header navigation to the navigation render hook', function (): void {
    $header = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/header/index.blade.php');
    $layoutArea = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/layout/area.blade.php');
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/FoundationThemeServiceProvider.php');

    expect($header)->toContain("scenario: 'theme-foundation-primary-navigation'")
        ->and($header)->toContain("target: 'capell::header.index'")
        ->and($header)->toContain('<x-capell::layout.area area="header" />')
        ->and($layoutArea)->toContain('capell-layout-builder::components.layout.area')
        ->and($provider)->toContain("->register('header'")
        ->and($header)->toContain('capell-navigation-menu-open-changed')
        ->and($header)->toContain('capell-product-header')
        ->and($header)->toContain('capell-product-nav-item')
        ->and($header)->toContain("getMeta('header_color')")
        ->and($header)->toContain("'var(--foundation-body-fg)'")
        ->and($header)->toContain("'var(--foundation-header-bg)'")
        ->and($header)->toContain("'var(--foundation-page-bg)'")
        ->and($header)->toContain("'header-logo h-10 max-h-10 w-auto'")
        ->and($header)->not->toContain('x-ref="toggleMenu"')
        ->and($header)->not->toContain('toggleMenu()')
        ->and($header)->not->toContain('Capell\\Navigation');
});

it('ships a stable premium default footer shell', function (): void {
    $footer = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/footer/index.blade.php');

    expect($footer)
        ->toContain('capell-product-footer')
        ->toContain('capell-product-footer__inner')
        ->toContain("resolveFooterColor('footer_background_color', '#edf2ee')")
        ->toContain("resolveFooterColor('footer_dark_background_color', '#0b1716')")
        ->toContain("__('capell-theme-foundation::generic.footer')")
        ->toContain('<x-capell::layout.area area="footer" />');
});

it('uses complete shared navigation disclosure below the wide desktop breakpoint', function (): void {
    $header = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/header/index.blade.php');
    $navigation = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/chrome/navigation.blade.php');
    $mobileNavigation = file_get_contents(dirname(__DIR__, 2) . '/resources/views/theme/partials/mobile-navigation.blade.php');
    $chromeStyles = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/chrome.css');

    expect($header)
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

it('keeps the shared chrome blur desktop-only with an opaque narrow-screen fallback', function (): void {
    $chromeStyles = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/chrome.css');

    expect($chromeStyles)
        ->toMatch('/@supports \(backdrop-filter: blur\(1rem\)\)\s*\{[^}]*\.theme-chrome-nav\s*\{[^}]*backdrop-filter:\s*blur\(8px\);/s')
        ->not->toContain('saturate(')
        ->toMatch('/@media \(max-width: 767px\)\s*\{\s*\.theme-chrome-nav\s*\{\s*backdrop-filter:\s*none;\s*background:\s*var\(--theme-surface, #fcfffb\);/s');
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
        ->and($main)->toContain('RenderHookLocation::AfterContent')
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
    require_once __DIR__ . '/../Support/WidgetViewFixture.php';

    $widget = foundationWidgetViewFixture();
    $widget->setRelation('translation', new Translation(['title' => 'Distinctive showcase headline', 'content' => '<p>Prepared showcase description.</p>']));
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    try {
        $cta = view('capell-theme-foundation::components.widget.modern.cta-section', [
            'widget' => $widget, 'container' => [], 'containerKey' => 'main', 'containerWidth' => null,
            'loop' => (object) ['index' => 0], 'attributes' => new ComponentAttributeBag,
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
    $document = new RenderedDocument($cta);
    config(['capell-layout-builder.widget.skip_render_empty' => false]);
    $renderWidget = static function (string $name, Widget $widget): RenderedDocument {
        $widget->view_file = 'capell-theme-foundation::components.widget.modern.' . $name;
        $component = new Asset(
            container: [],
            containerKey: 'main',
            widgetIndex: 0,
            loop: (object) ['index' => 0],
            widget: $widget,
        );
        $view = $component->render(['attributes' => new ComponentAttributeBag, 'containerWidth' => null]);
        throw_unless($view instanceof View, RuntimeException::class);

        return new RenderedDocument($view->render());
    };
    Blaze::disable();
    try {
        $emptyHero = $renderWidget('hero-banner', foundationWidgetViewFixture(count: 0));
        $showcase = foundationWidgetViewFixture();
        foreach (foundationWidgetAssets($showcase) as $asset) {
            $record = $asset->getRelation('asset');
            throw_unless($record instanceof Model, RuntimeException::class);
            $record->setAttribute('meta', ['icon' => 'Accessible feature', 'link_url' => '/showcase', 'link_text' => 'Explore item']);
        }
        $cardGrid = $renderWidget('card-grid', $showcase);
        $featureList = $renderWidget('feature-list', $showcase);
        $gallery = $renderWidget('image-gallery', $showcase);
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
    expect($emptyHero->elements('//span[contains(@class, "ap-hero__panel-title") and normalize-space(.) != ""]'))->toHaveCount(1)
        ->and($emptyHero->elements('//span[contains(@class, "ap-hero__rail-title") and normalize-space(.) != ""]'))->toHaveCount(1)
        ->and($cardGrid->elements('//a[contains(@class, "ap-card__link") and @href="/showcase"]'))->toHaveCount(2)
        ->and($featureList->elements('//span[contains(@class, "ap-feature-item__icon") and normalize-space(.)="Accessible feature"]'))->toHaveCount(2)
        ->and($document->elements('//h2[normalize-space(.)="Distinctive showcase headline"]'))->toHaveCount(1)
        ->and($document->elements('//p[normalize-space(.)="Prepared showcase description."]'))->toHaveCount(1)
        ->and($document->elements('//p[contains(concat(" ", normalize-space(@class), " "), " ap-showcase-cta__proof-text ") and normalize-space(.) != ""]'))->toHaveCount(3)
        ->and($gallery->elements('//figcaption[contains(@class, "ap-gallery-caption")]'))->toHaveCount(2);
});

it('does not own premium demo kit homepage section styling', function (): void {
    $themeCss = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');

    expect($themeCss)->not->toContain('capell-widget-homepage-section');
});
