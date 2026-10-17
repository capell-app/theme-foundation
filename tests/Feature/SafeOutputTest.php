<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\FoundationTheme\View\Components\Actions;
use Capell\Frontend\Actions\Performance\RecordExtensionRenderContributionAction;
use Capell\Frontend\Facades\Frontend;
use Capell\Tests\Support\CssDeclarations;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Blaze\Blaze;
use Symfony\Component\Finder\Finder;

require_once __DIR__ . '/../Support/DomQueries.php';
require_once __DIR__ . '/../Support/PublicChromeFixture.php';
require_once __DIR__ . '/../Support/WidgetViewFixture.php';

use function Capell\FoundationTheme\Tests\Support\chromeElements;
use function Capell\FoundationTheme\Tests\Support\preparePublicChrome;
use function Capell\FoundationTheme\Tests\Support\renderPublicChromeView;

test('default theme escapes site titles and plain footer text', function (): void {
    $site = preparePublicChrome('<script>alert(1)</script>');
    $translation = $site->getRelation('translation');
    if (! $translation instanceof Translation) {
        throw new RuntimeException('Expected prepared site copy.');
    }
    $translation->setAttribute('meta', ['footer_copy' => '<p>Safe footer copy</p><script>alert(2)</script>']);
    foreach (['header.index', 'footer.index', 'footer.site-info'] as $name) {
        $html = renderPublicChromeView($name, ['site' => $site]);
        expect(domQueryCount($html, '//script[normalize-space(.)="alert(1)" or normalize-space(.)="alert(2)"]'))->toBe(0)
            ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
        if ($name === 'footer.index') {
            expect($html)->toContain('Safe footer copy')->not->toContain('alert(2)');
        }
    }
    $relatedSites = Blade::render('<x-capell::footer.related-sites :related-sites="$sites" />', ['sites' => collect([
        ['url' => '/safe-site', 'title' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(2)>', 'primaryColor' => '#123456'],
    ])]);
    expect(domQueryCount($relatedSites, '//a[@href="/safe-site"]'))->toBe(1)
        ->and(domQueryCount($relatedSites, '//script | //img[@onerror]'))->toBe(0)
        ->and($relatedSites)->toContain('&lt;script&gt;', '&lt;img');
});

test('content component sanitizes cms html before rendering', function (): void {
    $html = Blade::render('<x-capell::content :content="$content" />', [
        'content' => '<p>Safe <strong>formatting</strong>.</p><script>alert(1)</script><a href="javascript:alert(2)">Link</a>',
    ]);

    expect($html)->toContain('<strong>formatting</strong>')
        ->not->toContain('<script', 'javascript:', 'alert(');
});

test('outer Foundation sections deliver deferred form fragments in both anchor states', function (bool $anchorable): void {
    $url = 'https://example.test/_capell/foundation-dynamic-form/opaque-reference';
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        $html = view('capell-theme-foundation::components.widget.foundation-section', [
            'sectionView' => 'capell-theme-foundation::theme.sections.form',
            'section' => (object) ['heading' => 'Get in touch'],
            'sectionAnchor' => 'contact', 'anchorable' => $anchorable,
            'formDeliveryFragmentUrl' => $url,
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect($html)->toContain('data-deferred-fragment-url="' . $url . '"')
        ->not->toContain('_token', '<form');

    expect(domQueryCount($html, '//*[@id="contact"]'))->toBe($anchorable ? 1 : 0);
})->with([true, false]);

test('the public skip link keeps an explicit high contrast focus indicator', function (): void {
    preparePublicChrome(themeMeta: ['header' => false, 'footer' => false]);
    $html = Blade::render('<x-capell::layout><p>Prepared main content</p></x-capell::layout>');
    expect(domQueryCount($html, '//a[@href="#main" and contains(concat(" ", normalize-space(@class), " "), " capell-public-skip-link ")]'))->toBe(1)
        ->and(domQueryCount($html, '//*[@id="main"]'))->toBe(1);

    $focus = collect(CssDeclarations::fromFile(dirname(__DIR__, 2) . '/resources/css/theme-foundation.css'))
        ->where('selector', '.capell-public-skip-link:focus');
    expect($focus->where('contexts', [])->where('property', 'outline')->where('important', true)->pluck('value')->all())
        ->toContain('3px solid var(--foundation-focus-ring, #1d4ed8)')
        ->and($focus->where('contexts', ['@media (forced-colors: active)'])->where('property', 'outline-color')->where('important', true)->pluck('value')->all())
        ->toContain('Highlight');
});

test('public image source component renders URL images without admin metadata', function (): void {
    $html = Blade::render(
        '<x-capell::image-source image="/public-image.jpg" alt="Public image" />',
    );

    expect($html)
        ->toContain('src="/public-image.jpg"')
        ->toContain('alt="Public image"')
        ->toContain('capell-image-source')
        ->not->toContain('recordId')
        ->not->toContain('fieldPath')
        ->not->toContain('wire:');
});

test('default theme treats navigation as optional', function (): void {
    preparePublicChrome();
    $header = renderPublicChromeView('header.index');
    $footer = renderPublicChromeView('footer.index');
    expect(domQueryCount($header, '//header'))->toBe(1)
        ->and(domQueryCount($footer, '//footer'))->toBe(1)
        ->and($header)->toContain('Public site')
        ->and($footer)->toContain('Public site');
});

test('public layout output does not include debug widget comments', function (): void {
    $themePath = dirname(__DIR__, 2);

    $container = file_get_contents($themePath . '/resources/views/components/layout/container.blade.php');

    expect($container)
        ->toContain('...$presentation->classes()')
        ->not->toContain('theme_settings')
        ->not->toContain('surface_tone')
        ->not->toContain('<!-- {$widget->key} Widget')
        ->not->toContain("config('app.debug')");
});

test('language flag images reserve dimensions for stable public layout', function (): void {
    preparePublicChrome();
    $languages = [['id' => 2, 'url' => '/fr', 'flag' => 'fr', 'name' => 'French']];
    $html = Blade::render('<x-capell::languages :languages="$languages" />', compact('languages'));
    $header = Blade::render('<x-capell::header.menu.languages-dropdown :language="$language" :page-languages="$languages"><x-slot:trigger>Languages</x-slot:trigger></x-capell::header.menu.languages-dropdown>', [
        'language' => Frontend::language(), 'languages' => $languages,
    ]);
    foreach ([$html, $header] as $output) {
        $images = chromeElements($output, '//img[contains(@src, "/vendor/blade-country-flags/")]');
        expect(count($images))->toBeGreaterThan(0);
        foreach ($images as $image) {
            if (! $image instanceof DOMElement) {
                throw new RuntimeException('Expected a rendered language image.');
            }
            expect($image->getAttribute('width'))->toBe('16')
                ->and($image->getAttribute('height'))->toBe('16')
                ->and($image->getAttribute('alt'))->toBe('')
                ->and($image->getAttribute('loading'))->toBe('lazy');
        }
        expect(domQueryCount($output, '//a[@href="/fr"][normalize-space(.)="French"]'))->toBe(1);
    }
});

test('public action buttons mark their csrf output as non-cacheable', function (): void {
    Route::post('/public-actions/{action}', static fn (): string => 'ok')
        ->name('capell-public-actions.submit');
    Route::getRoutes()->refreshNameLookups();

    resolve(RecordExtensionRenderContributionAction::class)->clear();

    (new Actions(actions: [
        [
            'type' => 'public_action',
            'public_action_key' => 'request-access',
            'label' => 'Request access',
        ],
    ]))->render();

    $contribution = collect(resolve(RecordExtensionRenderContributionAction::class)->recorded())
        ->first(fn (mixed $record): bool => $record->contributionClass === Actions::class);

    expect($contribution?->cacheable)->toBeFalse()
        ->and($contribution?->sensitiveOutput)->toBeTrue();
});

test('public blade keeps data loading out of templates', function (): void {
    $themePath = dirname(__DIR__, 2);
    $violations = [];
    // Public templates may not perform database or media loading; retain only the loading API tokens.
    $forbiddenPatterns = [
        'DB::',
        '::query(',
        'NavigationLoader::',
        'PageLoader::',
        'SiteLoader::',
        'Frontend::site()->siteDomain',
        'loadMissing(',
        'relationLoaded(',
        'getMedia(',
    ];

    $files = (new Finder)
        ->files()
        ->in($themePath . '/resources/views')
        ->name('*.blade.php')
        ->notPath('components/filament')
        ->notPath('components/infolists');

    foreach ($files as $file) {
        $contents = $file->getContents();
        $relativePath = str_replace($themePath . '/', '', $file->getPathname());

        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = $relativePath . ' contains ' . $pattern;
            }
        }
    }

    expect($violations)->toBe(
        [],
        'Public Blade data-loading violations found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

test('prepared public chrome renders without database reads', function (): void {
    $site = preparePublicChrome();
    DB::enableQueryLog();
    try {
        foreach (['header.index', 'footer.index', 'footer.site-info'] as $name) {
            expect(renderPublicChromeView($name, ['site' => $site]))->toContain('Public site');
        }
        expect(DB::getQueryLog())->toBe([]);
    } finally {
        DB::disableQueryLog();
    }
});

test('demo contact page uses only the prepared current domain relation', function (): void {
    $site = preparePublicChrome();
    $page = new Page(['name' => 'Contact']);
    $page->setRelation('translation', new Translation(['title' => 'Contact']));
    $html = renderPublicChromeView('demo.contact-page', compact('site', 'page'));
    expect(domQueryCount($html, '//a[@href="/prepared-home"][normalize-space(.)="Public site"]'))->toBe(1)
        ->and(domQueryCount($html, '//h1[normalize-space(.)="Contact"]'))->toBe(1)
        ->and(domQueryCount($html, '//form[@action="#"]'))->toBe(0)
        ->and($html)->toContain(__('capell-theme-foundation::generic.contact_expectations'))
        ->not->toContain('wire:snapshot', 'fieldPath', 'recordId');
});

test('theme public views and assets avoid package and theme implementation markers', function (): void {
    $packagesRoot = dirname(__DIR__, 3);
    $packagePaths = [
        $packagesRoot . '/theme-foundation',
        ...glob($packagesRoot . '/theme-*') ?: [],
    ];
    $directories = [];
    // These confidential authoring and package markers must never ship in public assets or HTML.
    $forbiddenMarkers = [
        'data-theme-key',
        'data-capell-theme',
        'capell-theme capell-theme-',
        'class="capell-theme',
        '.capell-theme-',
        '--capell-theme-',
        'capell-theme-foundation-',
        'capell-app-body',
        'capell-app/theme-',
        'capell-app/theme-foundation',
        'model_id',
        'field_path',
    ];
    $violations = [];

    foreach ($packagePaths as $packagePath) {
        foreach (['resources/views', 'resources/css', 'resources/js'] as $relativeDirectory) {
            $directory = $packagePath . '/' . $relativeDirectory;

            if (is_dir($directory)) {
                $directories[] = $directory;
            }
        }
    }

    $files = (new Finder)
        ->files()
        ->in($directories)
        ->name(['*.blade.php', '*.css', '*.js']);

    foreach ($files as $file) {
        $contents = $file->getContents();
        $relativePath = str_replace($packagesRoot . '/', '', $file->getPathname());

        // The Inertia themes ship a client-side public-HTML sanitizer whose denylist
        // regex must name the very markers it strips (model_id, field_path). Naming a
        // token in order to remove it is the opposite of leaking it, so exempt that
        // single file from those two markers while keeping every other marker enforced.
        $isMarkerSanitizer = str_ends_with($relativePath, 'resources/js/Support/publicHtml.js');

        foreach ($forbiddenMarkers as $marker) {
            if ($isMarkerSanitizer && in_array($marker, ['model_id', 'field_path'], true)) {
                continue;
            }

            if (str_contains($contents, $marker)) {
                $violations[] = $relativePath . ' contains ' . $marker;
            }
        }
    }

    expect($violations)->toBe(
        [],
        'Theme public output marker leaks found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

test('reviewed public blade widgets do not read asset and page relations directly', function (): void {
    $themePath = dirname(__DIR__, 2);
    $files = [
        'resources/views/components/widget/modern/hero-banner.blade.php',
        'resources/views/components/widget/modern/image-gallery.blade.php',
        'resources/views/components/widget/modern/card-grid.blade.php',
        'resources/views/components/widget/asset/accordion.blade.php',
        'resources/views/components/widget/asset/carousel.blade.php',
        'resources/views/components/widget/asset/feature-item.blade.php',
        'resources/views/components/widget/asset/media.blade.php',
        'resources/views/components/widget/asset/widgets.blade.php',
    ];
    // Dereferencing these unhydrated relations can query or expose raw editor state in public Blade.
    $forbiddenPatterns = [
        '$page?->assets',
        '$attachment->asset',
        '$heroItem->asset',
        '$widget->assets',
        '$asset->asset->media',
        '$asset->asset->translation',
        '$widgetAsset->asset->translation',
        '$widgetAsset->asset->getMeta(',
        '$linkedPage->pageUrl',
    ];
    $violations = [];

    foreach ($files as $file) {
        $contents = file_get_contents($themePath . '/' . $file);

        if (! is_string($contents)) {
            throw new RuntimeException(sprintf('Expected %s to be readable.', $file));
        }

        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = $file . ' contains ' . $pattern;
            }
        }
    }

    expect($violations)->toBe(
        [],
        'Reviewed public Blade relation access found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

test('public hero, page content and collection titles honour the primary heading context', function (): void {
    $widget = foundationWidgetViewFixture(count: 0);
    $widget->setRelation('backgroundImage', null);
    $data = ['widget' => $widget, 'title' => 'Public hero', 'content' => 'Prepared copy', 'container' => [], 'containerKey' => 'main', 'containerWidth' => null, 'loop' => (object) ['index' => 0], 'assets' => collect(), 'assetRenderDataItems' => []];
    $html = renderPublicChromeView('widget.modern.hero-banner', $data);
    expect(domQueryCount($html, '//h1[normalize-space(.)="Public hero"]'))->toBe(1)
        ->and(domQueryCount($html, '//button[contains(@class, "ap-hero__slideshow-play")]'))->toBe(0);
    $html = renderPublicChromeView('widget.modern.hero-banner', [...$data, 'hasPrimaryHeading' => true]);
    expect(domQueryCount($html, '//h2'))->toBe(1)->and(domQueryCount($html, '//h1'))->toBe(0);
    foreach (['image-gallery', 'card-grid'] as $name) {
        $html = renderPublicChromeView('widget.modern.' . $name, $data);
        expect($html)->not->toContain('No images configured', 'No cards configured');
    }
    $page = new Page(['name' => 'Page copy']);
    $page->setRelation('translation', new Translation(['title' => 'Page copy', 'content' => '<p>Prepared body</p>']));
    $page->setRelation('image', null);
    $page->setRelation('blueprint', null);
    $html = renderPublicChromeView('widget.page.content', [...$data, 'widgetData' => ['meta' => ['show_page_title' => true]], 'pageRecord' => $page, 'hasPrimaryHeading' => true]);
    expect(domQueryCount($html, '//h2'))->toBe(1)->and(domQueryCount($html, '//h1'))->toBe(0);
    // Saved configurations need fresh widgets: metadata resolved by an earlier render is cached.
    $widget = foundationWidgetViewFixture(['heading_tag' => 'h3'], count: 0);
    $widget->setRelation('translation', new Translation(['title' => 'Collection title', 'content' => '']));
    $currentPage = Frontend::page();
    if (! $currentPage instanceof Page) {
        throw new RuntimeException('Expected a prepared page for collection headings.');
    }
    $currentPage->setRelation('translation', $page->getRelation('translation'));
    $currentPage->setRelation('type', null);
    config(['capell-layout-builder.widget.skip_render_empty' => false]);
    foreach ([false, true] as $showPageTitle) {
        $html = renderFoundationWidgetFixture('pages', $widget, ['pages' => collect(), 'widgetData' => ['meta' => []], 'componentItem' => 'fixture.asset', 'showPageTitle' => $showPageTitle, 'showPageContent' => false, 'index' => 0]);
        expect($html)->toContain($showPageTitle ? '<h1' : '<h3');
    }
});

test('standard content keeps inherited theme colours in dark mode', function (): void {
    $themeStyles = file_get_contents(dirname(__DIR__, 2) . '/resources/css/theme/theme.css');
    expect($themeStyles)->toContain('--tw-prose-body: var(--foundation-body-fg)')
        ->toContain('.content-component.capell-standard-page-content')
        ->toMatch('/\.dark \.content-component\.capell-standard-page-content.*?--tw-prose-body: currentColor.*?--tw-prose-headings: currentColor/s')
        ->toContain('color: inherit !important');
});

test('theme public blade avoids unstable identifier helpers', function (): void {
    $render = static fn (string $heading): string => view('capell-theme-foundation::theme.sections.content-listing', [
        'section' => (object) ['heading' => $heading, 'summary' => 'Prepared cards', 'variant' => 'spotlight', 'items' => [
            ['title' => 'First story', 'url' => '/first'], ['title' => 'Second story', 'url' => '/second'],
        ]],
    ])->render();
    $first = $render('Featured stories');
    $second = $render('Featured stories');
    $other = $render('Other stories');
    $ids = static fn (string $html): array => array_map(static fn (DOMNode $node): string => $node instanceof DOMElement ? $node->getAttribute('id') : '', chromeElements($html, '//*[@role="tab" or @role="tabpanel"]'));
    expect($ids($first))->not->toBeEmpty()->toBe($ids($second))->not->toBe($ids($other));
    foreach (chromeElements($first, '//*[@role="tab"]') as $tab) {
        if (! $tab instanceof DOMElement) {
            throw new RuntimeException('Expected a rendered story tab.');
        }
        $panelId = $tab->getAttribute('aria-controls');
        expect(domQueryCount($first, '//*[@role="tabpanel"][@id="' . $panelId . '"][@aria-labelledby="' . $tab->getAttribute('id') . '"]'))->toBe(1);
    }
});

test('reviewed foundation chrome avoids accessibility regressions', function (): void {
    preparePublicChrome();
    $footer = renderPublicChromeView('footer.index');
    $scrollLabel = __('capell-theme-foundation::generic.scroll_to_top');
    expect(domQueryCount($footer, '//button[@type="button"][@aria-label="' . $scrollLabel . '"]'))->toBe(1)
        ->and(domQueryCount($footer, '//a[starts-with(@href,"javascript:")]'))->toBe(0);
    $social = renderPublicChromeView('footer.social-links', ['links' => [
        ['url' => 'https://example.test/social', 'safe_url' => 'https://example.test/social', 'title' => 'Follow us'],
    ]]);
    expect(domQueryCount($social, '//a[@href="https://example.test/social"][@target="_blank"][@rel="nofollow noopener"][@aria-label="Follow us"]'))->toBe(1);
    $related = renderPublicChromeView('footer.related-sites', ['relatedSites' => collect([
        ['url' => '/related', 'title' => 'Related site', 'description' => 'More stories', 'primaryColor' => 'red; background:url(javascript:alert(1))'],
    ])]);
    expect(domQueryCount($related, '//a[@href="/related"]'))->toBe(1)
        ->and(domQueryCount($related, '//*[@role="menu" or @role="menuitem"]'))->toBe(0)
        ->and($related)->not->toContain('javascript:', 'background:url');
});

test('public blade style tokens use css color safety resolver', function (): void {
    $unsafe = 'red; background:url(javascript:alert(1))';
    $site = preparePublicChrome(themeMeta: [
        'header_divider' => true, 'header_border_color' => $unsafe, 'header_color' => $unsafe,
        'header_background_color' => $unsafe, 'footer_color' => $unsafe,
        'footer_background_color' => $unsafe, 'footer_border_color' => $unsafe,
    ]);
    $site->setAttribute('meta', ['brand_color' => $unsafe]);
    foreach (['header.index', 'footer.index', 'app.head.tokens'] as $name) {
        $html = renderPublicChromeView($name);
        expect(domQueryCount($html, '//style'))->toBeGreaterThan(0)
            ->and($html)->not->toContain('javascript:', 'background:url', 'alert(1)');
    }
    preparePublicChrome(themeMeta: ['header_color' => '#123456', 'footer_color' => '#123456']);
    foreach (['header.index', 'footer.index'] as $name) {
        expect(renderPublicChromeView($name))->toContain('rgb(18, 52, 86)');
    }
});
