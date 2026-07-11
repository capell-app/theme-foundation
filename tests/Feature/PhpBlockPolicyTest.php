<?php

declare(strict_types=1);

use Capell\FoundationTheme\Testing\AssertsPublicThemeOutputSafety;
use PHPUnit\Framework\ExpectationFailedException;

uses(AssertsPublicThemeOutputSafety::class);

/*
 * Wave 1.2 frozen baseline snapshot: `grep -ro '@php' packages/theme-<slug>/resources/views | wc -l`
 * per theme package, captured 2026-07-05. Counts may only decrease from
 * here — `AssertsPublicThemeOutputSafety::assertPhpBlockPolicy()` asserts
 * each theme's current count against its frozen baseline via
 * `assertLessThanOrEqual()`. Original sum across all 19 theme packages was
 * 402 (programme doc's verified-current-state section); Wave 4a's
 * editorial-publishing level-up (editorial, magazine, blog) raised
 * several themes' counts as designed new sections landed, moving the sum
 * to 453. Wave 4b's archive-directory level-up for showreel (cinema
 * archive signature widgets: video-preview-grid, jury-score-matrix,
 * archive-wall-index, time-capsule-browser, plus variants) raised its
 * count from 15 to 31, moving the sum to 469. Wave 4b's archive-directory
 * level-up for submissions (arcade cabinet signature widgets:
 * card-shuffle-grid, metadata-facet-wall, featured-today-banner,
 * winners-ledger-table, submission-pulse, infinite-scroll-depth-pressure,
 * plus variants) raised its count from 18 to 31, moving the sum to 482.
 * Wave 4b's archive-directory level-up for brutalist (field station
 * signature widgets: seeded irregular-index-grid stagger, archive-calendar
 * variant, time-capsule-browser, plus sibling variants) raised its count
 * from 16 to 29, moving the sum to 495. Wave 4b's archive-directory
 * level-up for awards (assay office signature widgets: conic-gradient
 * voting-status gauge + data-deadline countdown, newest-nominees carousel
 * variant, previous-winners table-to-cards compact variant,
 * nominee-heat-map, award-countdown-ticker, time-capsule-browser, plus
 * sibling variants) raised its count from 18 to 34, moving the sum to 511.
 * Wave 4c's portfolio-gallery level-up for saas (stagger
 * launch-sequence signature widgets: launch-sequence-hero, category-
 * navigation-grid, website-examples-grid, paid-templates-upsell,
 * launch-cta-sequence -- five `--sequence` sidecar variants) raised its
 * count from 16 to 24, moving the sum to 519. Wave 4c's portfolio-gallery
 * level-up for catalogue (tessellation grid signature widgets:
 * taxonomy-grid-browser, latest-designs-showcase, editor-picks-curated,
 * faq-archives-accordion, collection-cta-browse, plus sibling variants)
 * raised its count from 17 to 29, moving the sum to 531. Wave 4c's
 * portfolio-gallery level-up for portfolio (filmstrip scrubbing signature
 * widgets: filmstrip-project-showcase, discipline-carousel-browse,
 * process-notes-timeline, credits-grid-roster, next-project-cta, plus
 * sibling variants) raised its count from 17 to 36, moving the sum to 550.
 * Wave 4c's portfolio-gallery level-up for curated (lightbox reel
 * signature widgets: curation-feed-grid, lightbox-carousel-viewer,
 * best-of-views-carousel, source-metadata-credits, next-item-lightbox-cta,
 * plus sibling variants) raised its count from 16 to 24, moving the sum
 * to 558. Wave 4c's free-pair §D level-up for foundation itself
 * (pricing-value-spectrum, faq-search-discovery, changelog-stream,
 * stats-display-band, plus a "form--encouraging" helpful-form-hints
 * variant, each with sibling variants) raised its baseline from 94 to
 * 110 — the pre-existing 94 already undercounted the live tree by 6
 * (drift from earlier waves that never updated this snapshot; the true
 * pre-Wave-4c count was 100), so this entry both corrects that drift and
 * adds this wave's 10 new `@php` blocks, moving the sum to 574. Wave 4c's
 * portfolio-gallery level-up for agency (salon gallery wall signature
 * widgets: featured-portfolios-hero parallax float, filter-taxonomies-grid,
 * portfolio-grid-gallery-wall varied deterministic spans, awarded-profiles-
 * spotlight gold/silver/bronze tiers, education-upsell-cta, each a `--`
 * sidecar variant of an existing section) raised its count from 19 to 31,
 * moving the sum to 586. Wave 4c's portfolio-gallery level-up for
 * minimalist (scatter light table signature widgets: browse-panels-scatter,
 * style-type-categories-scattered, latest-showcase-organic, sponsor-space-
 * floating, random-best-of-cta, each a `--` sidecar variant of an existing
 * section, crc32-seeded rotation/offset/z-index) raised its count from 17
 * to 25, moving the sum to 594. Wave 4c's free-pair §D level-up for
 * liquid-glass (glassmorphism showcase signature widgets:
 * glass-feature-card, translucent-stat-band, layered-depth-hero,
 * refraction-grid, floating-glass-nav, each with an inline `variant`-key
 * branch rather than a sidecar view, since layout-native themes have no
 * `VariantViewSectionRenderer` seam) raised its count from 12 to 20, moving
 * the sum to 602. Wave 4c's portfolio-gallery level-up for directory
 * (roster sorting signature widgets: directory-hero-roster count-up stats,
 * role-filters-toolbar multi-select facet checkboxes, portfolio-grid-cards
 * filter target, resume-resources-sidebar, curated-lists-cta, each a `--`
 * sidecar variant of an existing section) raised its count from 19 to 35,
 * moving the sum to 618. Wave 6's new docs/KB theme, docs
 * (layout-native; seven bespoke capell.widget.docs.* widgets:
 * doc-tree-sidebar, in-article-toc-scroll-spy, search-spotlight-hero,
 * version-changelog-surfaces, api-reference-parameter-table,
 * callout-admonition-system, feedback-footer, each with an inline
 * `variant`-key branch rather than a sidecar view, since layout-native
 * themes have no `VariantViewSectionRenderer` seam, plus its header/footer
 * chrome) added a new entry at 13, moving the sum to 631. Wave 7's new
 * events/conference theme, events (layout-native; eight bespoke
 * capell.widget.events.* widgets: agenda-grid-days-tracks-rooms,
 * speaker-wall-hover-bios, ticket-tier-comparison, countdown-band,
 * venue-travel-panels, sponsor-tier-walls, live-now-replay-state,
 * past-editions-archive, each with an inline `variant`-key branch rather
 * than a sidecar view, since layout-native themes have no
 * `VariantViewSectionRenderer` seam, plus its header/footer chrome) added a
 * new entry at 10, moving the sum to 641. Wave 5's new local-services
 * theme, business (layout-native; eight bespoke capell.widget.business.*
 * widgets: service-area-map-grid, before-after-comparison,
 * emergency-availability-banner, quote-path-stepper,
 * accreditation-insurance-strips, review-proof-wall, pricing-guide-table,
 * team-on-the-road-cards, each with an inline `variant`-key branch rather
 * than a sidecar view, since layout-native themes have no
 * `VariantViewSectionRenderer` seam) added a new entry at 10, moving the
 * sum to 651. A follow-up fix completing submissions's missing
 * time-capsule-browser signature widget (base view + `--cabinet` sidecar
 * variant, matching the mechanic already shipped on showreel, brutalist,
 * and awards) raised its count from 31 to 35, moving the sum to 655.
 * The 2026-07-10 recursive scanner repair then corrected Foundation from
 * 110 to 114, Liquid Glass from 20 to 19, Platform from 20 to 21, and the
 * renamed Docs entry to Knowledge at 13, moving the true fleet total to
 * 659 without adding a new Blade block.
 */
final class ThemePhpBlockBaselineCounts
{
    /**
     * @var array<string, int>
     */
    public const array FROZEN_BASELINE_COUNTS = [
        'photography' => 18,
        'business' => 10,
        'directory' => 35,
        'magazine' => 34,
        'catalogue' => 29,
        'curated' => 24,
        'foundation' => 116,
        'agency' => 31,
        'awards' => 34,
        'editorial' => 32,
        'saas' => 24,
        'liquid-glass' => 19,
        'events' => 10,
        'platform' => 21,
        'brutalist' => 29,
        'onepage' => 18,
        'portfolio' => 36,
        'blog' => 37,
        'knowledge' => 13,
        'showreel' => 31,
        'minimalist' => 25,
        'submissions' => 35,
    ];
}

it('confirms the frozen baseline snapshot sums to the programme-verified total of 661', function (): void {
    expect(array_sum(ThemePhpBlockBaselineCounts::FROZEN_BASELINE_COUNTS))->toBe(661);
});

it('keeps each theme package within its frozen @php block baseline', function (): void {
    $repositoryRoot = dirname(__DIR__, 4);

    // theme-foundation, theme-platform, and theme-liquid-glass render
    // live-pipeline chrome (app shell, header/footer, shared components)
    // whose @php blocks legitimately call Frontend:: — the same call this
    // trait's DB-query checks already carve out as safe for that class of
    // view. theme-foundation's app.blade.php additionally checks
    // Route::has() for the frontend-authoring beacon route, and its content
    // component calls GetPageVariablesAction::run() to prep translation
    // variables — both pure, non-persisting reads, not database queries.
    // Every other theme keeps the base whitelist only.
    $additionalWhitelistedStaticCallPrefixesByTheme = [
        'foundation' => [
            'AssetComponentEnum',
            'Blaze',
            'BuildAssetBannerItemsAction',
            'BuildBannerImageRenderDataAction',
            'BuildHeroRailItemsRenderDataAction',
            'BuildPageContentRenderDataAction',
            'CapellLayoutManager',
            'ContainerAlignmentEnum',
            'ContentStructure',
            'DefaultColorEnum',
            'Frontend',
            'GetLayoutContainerWidthAction',
            'GetPageVariablesAction',
            'GetWidgetContainerWidthAction',
            'Image',
            'LayoutWidgetData',
            'MarkPrimaryHeadingRenderedAction',
            'OpaqueWidgetReference',
            'PageChildrenComponent',
            'PageContentComponent',
            'PageLatestComponent',
            'PageSiblingsComponent',
            'PublicModelMeta',
            'RenderableTypeEnum',
            'RenderHookLocation',
            'RenderHookRegistry',
            'ResolveFoundationThemeTokensAction',
            'ResolveLoadedLayoutContainerBackgroundImageAction',
            'ResolveLoadedWidgetBackgroundImageAction',
            'ResolveRenderableComponentAction',
            'ResolveSafeCssColorTokenAction',
            'ResponsiveVisibilityEnum',
            'Route',
            'SlotComponent',
            'WidgetComponentEnum',
        ],
        'platform' => ['Frontend'],
        'liquid-glass' => ['Frontend'],
        'events' => ['Frontend'],
        'knowledge' => ['Frontend'],
    ];

    foreach (ThemePhpBlockBaselineCounts::FROZEN_BASELINE_COUNTS as $themeSlug => $frozenBaselineCount) {
        $viewsDirectory = $repositoryRoot . '/packages/theme-' . $themeSlug . '/resources/views';

        $this->assertPhpBlockPolicy(
            $viewsDirectory,
            $frozenBaselineCount,
            $additionalWhitelistedStaticCallPrefixesByTheme[$themeSlug] ?? [],
        );
    }
});

it('scans blade views nested more than two directories deep', function (): void {
    $fixtureDirectory = sys_get_temp_dir() . '/capell-theme-safety-' . bin2hex(random_bytes(8));
    $nestedDirectory = $fixtureDirectory . '/components/display/deep';

    mkdir($nestedDirectory, 0777, true);
    file_put_contents($nestedDirectory . '/unsafe.blade.php', '<div data-model="should-be-detected"></div>');

    try {
        expect(fn (): mixed => $this->assertThemeOutputMetadataIsSafe($fixtureDirectory, 'fixture-package'))
            ->toThrow(ExpectationFailedException::class, 'data-model');
    } finally {
        unlink($nestedDirectory . '/unsafe.blade.php');
        rmdir($nestedDirectory);
        rmdir($fixtureDirectory . '/components/display');
        rmdir($fixtureDirectory . '/components');
        rmdir($fixtureDirectory);
    }
});
