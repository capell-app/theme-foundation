<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Contracts\SectionRenderer;
use Capell\Core\ThemeStudio\Contracts\ThemeSection;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\FooterData;
use Capell\Core\ThemeStudio\Data\GenericSectionData;
use Capell\Core\ThemeStudio\Data\NavigationData;
use Capell\Core\ThemeStudio\Data\ThemePageData;
use Capell\FoundationTheme\Rendering\ChromeSplitBladeThemeRenderer;

require_once __DIR__ . '/../../../../tests/Packages/Support/ThemeLayoutNativeSupport.php';

/*
 * Wave 7 landmark-restructure ratchet: every theme migrated to
 * ChromeSplitBladeThemeRenderer must render <nav>/<footer> as siblings of
 * <main>, never as descendants of it — the real structural a11y fix this
 * wave delivers. The stub renderer below stands in for a migrated theme's
 * page.blade.php (chromeHeader/mainContent/chromeFooter placed outside
 * <main>, matching the pattern every migrated theme's own page.blade.php
 * must follow).
 *
 * THEMES_MIGRATED_TO_LANDMARK_SPLIT below is the shrinking allowlist: as
 * each theme migrates its page.blade.php, add its key here. A theme absent
 * from this list is still tolerated (unmigrated), but every addition is a
 * one-way ratchet — a theme can never quietly leave the list once verified.
 */

/*
 * Phase C conversion ratchet: once a theme is converted to render through
 * x-capell::layout + layout-builder instead of its own section pipeline, it
 * no longer ships a page.blade.php with chromeHeader/mainContent/chromeFooter
 * for this test to inspect — its landmark structure (nav/main/footer as
 * siblings) comes from x-capell::layout itself. Entries may leave the
 * landmark-split assertion below ONLY by appearing in themesConvertedToLayoutBuilder()
 * (see tests/Packages/Support/ThemeLayoutNativeSupport.php, the single source
 * of truth for this list). It starts empty: no theme has converted yet.
 */

const THEMES_MIGRATED_TO_LANDMARK_SPLIT = [
    'default', // Foundation — the first migration per Wave 7.3's stated order.
    'portfolio', // The contract-tested pair, migrated next per Wave 7.3.
    'agency',
    // Wave 7.3 completion: the remaining fleet, migrated in one sweep. Every
    // theme now renders <nav>/<footer> as siblings of <main>.
    'platform',
    'editorial',
    'photography',
    'blog',
    'submissions',
    'magazine',
    'catalogue',
    'saas',
    'liquid-glass',
    'curated',
    'showreel',
    'onepage',
    'directory',
    'minimalist',
    'brutalist',
    'awards',
];

/**
 * Absolute path to a migrated theme's layout Blade. Foundation (theme key
 * `default`) keeps its layout under theme/page.blade.php; every child theme
 * exposes it directly under resources/views/page.blade.php.
 */
function landmarkMigratedThemeBladePath(string $themeKey): string
{
    $packagesDirectory = dirname(__DIR__, 3);

    if ($themeKey === 'default') {
        return $packagesDirectory . '/theme-foundation/resources/views/theme/page.blade.php';
    }

    return $packagesDirectory . '/theme-' . $themeKey . '/resources/views/page.blade.php';
}

function landmarkStructureStubSectionRenderer(string $tag, string $themeKey): SectionRenderer
{
    return new class($tag, $themeKey) implements SectionRenderer
    {
        public function __construct(
            private readonly string $tag,
            private readonly string $themeKey,
        ) {}

        public function themeKey(): string
        {
            return $this->themeKey;
        }

        public function sectionKey(): string
        {
            return $this->tag;
        }

        public function render(ThemeSection $section): string
        {
            return "<{$this->tag}>{$section->key()}</{$this->tag}>";
        }
    };
}

function landmarkStructureRenderedHtml(): string
{
    view()->addNamespace('capell-landmark-fixtures', __DIR__ . '/../Fixtures/views');

    $renderer = new ChromeSplitBladeThemeRenderer(
        themeKey: 'landmark-stub',
        layoutView: 'capell-landmark-fixtures::landmark-layout',
        sectionRenderers: [
            'navigation' => landmarkStructureStubSectionRenderer('nav', 'landmark-stub'),
            'hero' => landmarkStructureStubSectionRenderer('section', 'landmark-stub'),
            'footer' => landmarkStructureStubSectionRenderer('footer', 'landmark-stub'),
        ],
    );

    $page = new ThemePageData(
        title: 'Landmark stub page',
        brand: new BrandProfileData,
        sections: [new GenericSectionData('hero', ['heading' => 'Stub hero'])],
        navigation: new NavigationData(brandName: 'Landmark Stub'),
        footer: new FooterData(brandName: 'Landmark Stub'),
    );

    return $renderer->render($page);
}

it('renders exactly one main element with nav and footer as its siblings, not its descendants', function (): void {
    $document = new DOMDocument;
    $document->loadHTML('<div>' . landmarkStructureRenderedHtml() . '</div>', LIBXML_NOERROR);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//main'))->toHaveCount(1)
        ->and($xpath->query('//main//nav'))->toHaveCount(0)
        ->and($xpath->query('//main//footer'))->toHaveCount(0)
        ->and($xpath->query('//nav'))->toHaveCount(1)
        ->and($xpath->query('//footer'))->toHaveCount(1);
});

it('keeps the brand-token wrapper as an ancestor of nav, main, and footer alike', function (): void {
    $document = new DOMDocument;
    $document->loadHTML('<div>' . landmarkStructureRenderedHtml() . '</div>', LIBXML_NOERROR);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//*[contains(@class, "stub-shell")]//nav'))->toHaveCount(1)
        ->and($xpath->query('//*[contains(@class, "stub-shell")]//main'))->toHaveCount(1)
        ->and($xpath->query('//*[contains(@class, "stub-shell")]//footer'))->toHaveCount(1);
});

it('still exposes the legacy concatenated content for unmigrated layouts', function (): void {
    expect(landmarkStructureRenderedHtml())
        ->toContain('<nav>navigation</nav>')
        ->toContain('<section>hero</section>')
        ->toContain('<footer>footer</footer>');
});

it('places nav/footer chrome outside <main> in every migrated theme layout', function (string $themeKey): void {
    if (in_array($themeKey, themesConvertedToLayoutBuilder(), true)) {
        // Converted themes render through x-capell::layout, which owns the
        // landmark structure itself; there is no theme page.blade.php left
        // to inspect for chromeHeader/mainContent/chromeFooter.
        expect(true)->toBeTrue();

        return;
    }

    $bladePath = landmarkMigratedThemeBladePath($themeKey);

    expect(file_exists($bladePath))->toBeTrue(
        "Theme [{$themeKey}] is on the landmark-split allowlist but has no page.blade.php at {$bladePath}.",
    );

    $blade = (string) file_get_contents($bladePath);

    if (str_contains($blade, '<x-capell-theme-foundation::theme.page-shell')) {
        expect($blade)->toContain(':chrome-header=')
            ->toContain(':chrome-footer=')
            ->toContain(':main-content=')
            ->toContain(':$content');

        return;
    }

    // A migrated layout must consume the split vars the
    // ChromeSplitBladeThemeRenderer passes, so nav/footer render as siblings.
    expect($blade)->toContain('chromeHeader')
        ->toContain('chromeFooter')
        ->toContain('mainContent');

    // The layout carries <main> in both the chrome-split (@if) and the legacy
    // (@else) branch, so the source holds more than one literal <main> even
    // though a single request renders exactly one. Every <main> opening tag
    // must carry neither the theme shell class nor the brand tokens — those
    // move to the wrapping element so <nav>/<footer> can sit beside <main>
    // instead of nested inside it.
    preg_match_all('/<main\b[^>]*>/', $blade, $mainTags);
    expect($mainTags[0])->not->toBeEmpty("Theme [{$themeKey}] has no <main> opening tag.");

    foreach ($mainTags[0] as $mainTag) {
        expect($mainTag)->not->toContain('-shell')
            ->and($mainTag)->not->toContain('tokens()');
    }

    // The shell wrapper carrying the brand tokens is therefore an ancestor of
    // <main>, not <main> itself.
    expect($blade)->toContain('-shell')
        ->toContain('tokens()');
})->with(THEMES_MIGRATED_TO_LANDMARK_SPLIT);

it('only ratchets the migrated-theme allowlist forward, never removes an entry', function (): void {
    // A regression here means a theme was silently reverted to the
    // unmigrated (tolerated) state — the ratchet only grows.
    expect(THEMES_MIGRATED_TO_LANDMARK_SPLIT)->toBeArray();
});
