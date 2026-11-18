<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Themes;

/**
 * Sections a retired theme offered that its successor has under another name.
 *
 * `capell:theme-migrate` points each of these widgets at the successor section
 * that shows the same content, so a page keeps rendering its items after the
 * move. The widget's stored data is never removed: fields the successor
 * section does not display stay saved and are listed in the preview, and the
 * rollback report restores the original section type. A retired section with
 * no entry here and no same-named successor view still blocks the move.
 *
 * A mapping onto a section that shows no list (a hero, say) sets
 * `requires_items` to false, so a widget without items can still move.
 *
 * @phpstan-type SectionMapping array{type: string, items_from: ?string, not_shown: list<string>, requires_items?: bool}
 */
final class RetiredThemeSectionMap
{
    /**
     * Keyed by the saved section type; a variant is stored separately in
     * meta.variant and falls back to the successor section's default layout.
     *
     * @var array<string, array<string, SectionMapping>> retired theme key => retired section type => successor mapping
     */
    private const array MAP = [
        'agency' => [
            'portfolio-grid' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.id', 'items.*.category']],
            'featured-portfolios' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => ['items.*.id', 'items.*.award']],
            'filter-taxonomies' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => ['items.*.count']],
            'creator-directory' => ['type' => 'project-feed', 'items_from' => 'stories', 'not_shown' => ['items.*.id', 'items.*.category', 'items.*.stat_label', 'items.*.stat_value']],
            'awarded-profiles' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => ['label', 'url', 'items.*.id', 'items.*.award']],
            'education-upsell' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => []],
        ],
        'onepage' => [
            'showcase-hero' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.note']],
            'one-page-grid' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['startingFolio', 'items.*.category']],
            'category-tabs' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => ['items.*.range']],
            'build-resources' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => ['ctaLabel', 'ctaUrl', 'cta_label', 'cta_url']],
            'templates-sections' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['label', 'url']],
            'tools-sponsors' => ['type' => 'credits-tools', 'items_from' => 'stories', 'not_shown' => ['items.*.category', 'items.*.meta']],
        ],
        'showreel' => [
            'archive-hero' => ['type' => 'creator-hero', 'items_from' => null, 'not_shown' => ['imageUrl'], 'requires_items' => false],
            'archive-wall-index' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => []],
            'date-filter-rail' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => ['panelKicker', 'panelNote']],
            'featured-project' => ['type' => 'creator-hero', 'items_from' => null, 'not_shown' => ['label', 'url', 'href', 'credits', 'creditsHeading', 'imageUrl'], 'requires_items' => false],
            'jury-score-explainer' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => []],
            'media-credits' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => []],
            'time-capsule-browser' => ['type' => 'process-notes-timeline', 'items_from' => null, 'not_shown' => ['eras', 'items.*.items', 'items.*.previewItems']],
            'winner-list' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['empty', 'emptyTitle', 'items.*.tier', 'items.*.credit', 'items.*.score']],
        ],
        'submissions' => [
            'card-shuffle-grid' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['slug', 'pageSeed', 'items.*.id']],
            'featured-today-banner' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['tag', 'deadline', 'nextRotationAt', 'rotationLabel', 'pick']],
            'infinite-scroll-depth-pressure' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => []],
            'metadata-facet-wall' => ['type' => 'discipline-filters', 'items_from' => 'groups', 'not_shown' => ['items.*.chips', 'items.*.items', 'items.*.label', 'items.*.count']],
            'profiles-resources' => ['type' => 'content-listing', 'items_from' => 'stories', 'not_shown' => []],
            'sponsor-modules' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => ['badge']],
            'submission-pulse' => ['type' => 'proof', 'items_from' => 'stats', 'not_shown' => ['bars', 'sevenDay', 'chartLabel']],
            'time-capsule-browser' => ['type' => 'process-notes-timeline', 'items_from' => null, 'not_shown' => ['cycles', 'items.*.items', 'items.*.previewItems']],
            'winners-ledger-table' => ['type' => 'content-listing', 'items_from' => 'rows', 'not_shown' => ['label', 'url', 'items.*.studio', 'items.*.medium', 'items.*.cycle', 'items.*.year']],
        ],
        'magazine' => [
            'city-guides' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => ['items.*.x', 'items.*.y']],
            'columnists' => ['type' => 'opinion-grid-with-bylines', 'items_from' => null, 'not_shown' => ['items.*.latest_title', 'items.*.latest_url', 'items.*.latest_date']],
            'cultural-dispatch-timeline' => ['type' => 'live-event-timeline', 'items_from' => null, 'not_shown' => ['items.*.date', 'items.*.url']],
            'lead-dispatch' => ['type' => 'top-stories', 'items_from' => null, 'not_shown' => ['items.*.image', 'items.*.imageUrl', 'items.*.category', 'items.*.meta']],
            'photo-essay' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => ['items.*.image', 'items.*.caption']],
            'radio-audio' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => ['audio_title', 'audio_url', 'transcript', 'label', 'url']],
            'shop-books' => ['type' => 'content-listing', 'items_from' => 'stories', 'not_shown' => []],
            'travel-culture' => ['type' => 'opinion-grid-with-bylines', 'items_from' => null, 'not_shown' => ['items.*.image', 'items.*.imageUrl']],
        ],
        'curated' => [
            'app-website-icons' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.category']],
            'best-of-views' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => ['label', 'url', 'button', 'buttonUrl', 'items.*.views']],
            'category-tabs' => ['type' => 'discipline-filters', 'items_from' => 'tabs', 'not_shown' => ['latestLabel', 'latestUrl', 'latest_label', 'latest_url', 'items.*.label']],
            'curation-feed' => ['type' => 'project-feed', 'items_from' => 'entries', 'not_shown' => ['emptyHeading', 'emptyNote', 'items.*.author', 'items.*.category', 'items.*.domain', 'items.*.maker', 'items.*.source', 'items.*.tags']],
            'curation-feed-grid' => ['type' => 'project-feed', 'items_from' => 'entries', 'not_shown' => ['emptyHeading', 'emptyNote', 'items.*.category']],
            'feed-hero' => ['type' => 'creator-hero', 'items_from' => 'notes', 'not_shown' => ['date', 'updatedLabel', 'media_url', 'media_alt']],
            'form' => ['type' => 'form', 'items_from' => null, 'not_shown' => ['eyebrow', 'checklist'], 'requires_items' => false],
            'lightbox-carousel-viewer' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['label', 'ariaLabel'], 'requires_items' => false],
            'source-metadata' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => []],
        ],
        'saas' => [
            'category-navigation' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => []],
            'gallery-system' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => []],
            'paid-templates' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['label', 'url', 'items.*.framework', 'items.*.free', 'items.*.price']],
            'partner-blocks' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => []],
            'utility-hero' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['summary', 'action', 'linkLabel', 'linkUrl', 'searchPlaceholder', 'searchUrl'], 'requires_items' => false],
            'website-examples' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.templateUrl']],
        ],
        'paperdesk' => [
            'desk-scatter' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['filters', 'hint', 'items.*.kind', 'items.*.date', 'items.*.publishedAt']],
            'desk-index' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => ['items.*.kind', 'items.*.date', 'items.*.publishedAt']],
            'guestbook-wall' => ['type' => 'proof', 'items_from' => 'testimonials', 'not_shown' => ['items.*.quote', 'items.*.attribution']],
            'letter-hero' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['summary', 'postmark', 'formHandle', 'showContactForm', 'mailtoUrl'], 'requires_items' => false],
            'lost-and-found' => ['type' => 'proof', 'items_from' => 'links', 'not_shown' => ['searchUrl', 'directoryUrl', 'items.*.url']],
            'materials-tray' => ['type' => 'proof', 'items_from' => 'attributes', 'not_shown' => ['items.*.note']],
            'polaroid-strip' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.kind', 'items.*.date', 'items.*.publishedAt']],
            'process-bench' => ['type' => 'process-notes-timeline', 'items_from' => 'steps', 'not_shown' => ['items.*.number']],
            'ticket-stub' => ['type' => 'proof', 'items_from' => 'details', 'not_shown' => ['ctaLabel', 'url', 'bookingUrl', 'mailtoUrl']],
            'wax-seal-cta' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['ctaLabel', 'mark'], 'requires_items' => false],
        ],
        'catalogue' => [
            'blog-mission' => ['type' => 'content-listing', 'items_from' => 'stories', 'not_shown' => ['statement']],
            'editor-picks' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => ['countNote', 'items.*.category', 'items.*.facet', 'items.*.label', 'items.*.tags']],
            'faq-archives' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => ['countNote']],
            'filter-hero' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => ['action', 'countNote', 'hintLabel', 'searchPlaceholder', 'items.*.count', 'items.*.facet']],
            'latest-designs' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['countNote', 'label', 'url', 'items.*.category', 'items.*.facet', 'items.*.label', 'items.*.tags']],
            'taxonomy-grid-browser' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['countNote', 'facets', 'noMatchesMessage', 'items.*.facet', 'items.*.tags']],
            'taxonomy-navigation' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => ['countNote', 'items.*.chips', 'items.*.count', 'items.*.links', 'items.*.note']],
        ],
        'directory' => [
            'curated-lists' => ['type' => 'content-listing', 'items_from' => 'stories', 'not_shown' => []],
            'curated-lists-cta' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['featuredList'], 'requires_items' => false],
            'directory-hero' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => ['rankLabel']],
            'directory-hero-roster' => ['type' => 'proof', 'items_from' => 'stats', 'not_shown' => ['items.*.prefix', 'items.*.suffix', 'items.*.decimals']],
            'portfolio-grid' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.available', 'items.*.category', 'items.*.label', 'items.*.location', 'items.*.tags']],
            'portfolio-grid-cards' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['rosterTarget', 'items.*.available', 'items.*.category', 'items.*.discipline', 'items.*.featured', 'items.*.location', 'items.*.roleLabel', 'items.*.verified']],
            'profile-detail' => ['type' => 'credits-grid-roster', 'items_from' => null, 'not_shown' => ['profile', 'gallery', 'galleryCaption', 'items.*.bio', 'items.*.facts', 'items.*.tags']],
            'resume-resources' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => ['label', 'url']],
            'resume-resources-sidebar' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => ['label', 'url']],
            'role-filters' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => ['note', 'items.*.active', 'items.*.count', 'items.*.meta']],
            'role-filters-toolbar' => ['type' => 'discipline-filters', 'items_from' => 'facets', 'not_shown' => ['availabilityLabel', 'facetLegend', 'locationLegend', 'locations']],
        ],
        'minimalist' => [
            'browse-panels' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => []],
            'editorial-posts' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => []],
            'gallery' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.alt', 'items.*.caption']],
            'latest-showcase' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.category']],
            'random-best-of' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => []],
            'sponsor-space' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => ['items.*.label']],
            'style-type-categories' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => ['items.*.count']],
        ],
        'brutalist' => [
            'archive-dates' => ['type' => 'process-notes-timeline', 'items_from' => 'stories', 'not_shown' => ['items.*.category']],
            'archive-wall' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['empty']],
            'irregular-index' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => []],
            'rough-links' => ['type' => 'discipline-filters', 'items_from' => null, 'not_shown' => []],
            'submission-markers' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => ['label', 'url', 'items.*.stamp']],
            'time-capsule-browser' => ['type' => 'process-notes-timeline', 'items_from' => null, 'not_shown' => ['eras', 'key', 'items.*.items']],
            'zine-annotations' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => []],
        ],
        'awards' => [
            'award-countdown-ticker' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['closed', 'deadline'], 'requires_items' => false],
            'creator-credits' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => []],
            'newest-nominees' => ['type' => 'project-feed', 'items_from' => null, 'not_shown' => ['items.*.category', 'items.*.score']],
            'nominee-heat-map' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => ['items.*.category', 'items.*.meta', 'items.*.score']],
            'previous-winners' => ['type' => 'related-projects', 'items_from' => null, 'not_shown' => ['label', 'url', 'href']],
            'score-criteria' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => ['items.*.weight']],
            'time-capsule-browser' => ['type' => 'process-notes-timeline', 'items_from' => null, 'not_shown' => ['items.*.items']],
            'voting-status' => ['type' => 'credits-tools', 'items_from' => null, 'not_shown' => ['closed', 'deadline', 'deadline_label', 'fallback_url', 'live_poll_options', 'live_poll_reference', 'items.*.meta', 'items.*.votes']],
            'winner-hero' => ['type' => 'creator-hero', 'items_from' => null, 'not_shown' => ['href', 'imageUrl', 'label', 'name', 'readMoreLabel', 'scores', 'title', 'url'], 'requires_items' => false],
        ],
        'blog' => [
            'author-profiles' => ['type' => 'opinion-grid-with-bylines', 'items_from' => 'authors', 'not_shown' => ['items.*.bio', 'items.*.image']],
            'contextual-glossary' => ['type' => 'topic-navigation', 'items_from' => 'terms', 'not_shown' => ['items.*.term', 'items.*.definition']],
            'editorial-statement' => ['type' => 'topic-navigation', 'items_from' => 'principles', 'not_shown' => []],
            'essay-index' => ['type' => 'content-listing', 'items_from' => 'essays', 'not_shown' => ['items.*.byline', 'items.*.dek', 'items.*.image']],
            'features' => ['type' => 'missed-it', 'items_from' => null, 'not_shown' => ['items.*.image']],
            'issue-archive' => ['type' => 'missed-it', 'items_from' => 'issues', 'not_shown' => []],
            'issue-contents' => ['type' => 'top-stories', 'items_from' => 'entries', 'not_shown' => ['issueLabel', 'issue_label', 'items.*.author', 'items.*.byline']],
            'quote-context' => ['type' => 'author-credibility-inline', 'items_from' => null, 'not_shown' => ['quote', 'text', 'image', 'attributionName', 'attributionRole', 'attributionImage'], 'requires_items' => false],
            'scroll-position-menu' => ['type' => 'topic-navigation', 'items_from' => 'headings', 'not_shown' => ['items.*.anchor', 'items.*.id', 'items.*.label']],
            'serialized-chapters' => ['type' => 'top-stories', 'items_from' => 'chapters', 'not_shown' => ['currentTitle', 'current_title', 'next', 'previous', 'items.*.current']],
            'subscription-panel' => ['type' => 'topic-navigation', 'items_from' => 'plans', 'not_shown' => ['action', 'formNote']],
        ],
        'broadsheet' => [
            'data-fact-columns' => ['type' => 'topic-navigation', 'items_from' => 'columns', 'not_shown' => ['items.*.facts']],
            'front-page-lede' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['standfirst', 'numbers'], 'requires_items' => false],
            'index-footer-band' => ['type' => 'proof', 'items_from' => null, 'not_shown' => ['items.*.delta']],
            'league-board' => ['type' => 'proof', 'items_from' => 'rows', 'not_shown' => ['caption', 'items.*.delta', 'items.*.sortValue', 'items.*.sparkline']],
            'market-summary-tiles' => ['type' => 'proof', 'items_from' => 'tiles', 'not_shown' => ['items.*.delta', 'items.*.sparkline']],
            'notice-board' => ['type' => 'content-listing', 'items_from' => 'notices', 'not_shown' => ['contactEmail', 'contactUrl', 'items.*.heading', 'items.*.body']],
            'session-clock' => ['type' => 'live-event-timeline', 'items_from' => 'sessions', 'not_shown' => ['timezone', 'items.*.start', 'items.*.end', 'items.*.startDisplay', 'items.*.endDisplay', 'items.*.schedule']],
            'sparkline-panel' => ['type' => 'proof', 'items_from' => 'series', 'not_shown' => ['expandUrl']],
            'ticker-strip' => ['type' => 'proof', 'items_from' => null, 'not_shown' => ['label', 'items.*.delta']],
            'wire-digest' => ['type' => 'live-event-timeline', 'items_from' => null, 'not_shown' => ['items.*.headline', 'items.*.source', 'items.*.datetime']],
        ],
        'folio' => [
            'colophon-page' => ['type' => 'proof', 'items_from' => 'details', 'not_shown' => []],
            'contents-leaders' => ['type' => 'proof', 'items_from' => 'entries', 'not_shown' => ['items.*.folio', 'items.*.url']],
            'cover-plate' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['edition', 'details', 'begin_reading_url'], 'requires_items' => false],
            'double-truck' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['continuation', 'facts', 'paragraphs'], 'requires_items' => false],
            'figure-table' => ['type' => 'proof', 'items_from' => 'rows', 'not_shown' => ['columns', 'table_label']],
            'index-register' => ['type' => 'proof', 'items_from' => 'entries', 'not_shown' => ['items.*.folio', 'items.*.url']],
            'marginalia-note' => ['type' => 'breaking-news-ribbon', 'items_from' => null, 'not_shown' => ['eyebrow', 'reference'], 'requires_items' => false],
            'plate-gallery' => ['type' => 'video-row', 'items_from' => 'plates', 'not_shown' => ['items.*.image', 'items.*.alt', 'items.*.caption']],
            'pull-quote-leaf' => ['type' => 'author-credibility-inline', 'items_from' => null, 'not_shown' => ['heading', 'quote', 'attribution'], 'requires_items' => false],
            'timeline-folio' => ['type' => 'live-event-timeline', 'items_from' => 'entries', 'not_shown' => ['items.*.date']],
        ],
        'bistro' => [
            'chef-story' => ['type' => 'features', 'items_from' => null, 'not_shown' => ['caption', 'image', 'imageAlt', 'items.*.meta']],
            'cinematic-stage' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['beats', 'layers'], 'requires_items' => false],
            'dish-gallery' => ['type' => 'features', 'items_from' => null, 'not_shown' => ['items.*.imageAlt']],
            'hours-location' => ['type' => 'contact-split', 'items_from' => null, 'not_shown' => ['address', 'directionsLabel', 'directionsUrl', 'hours', 'hoursHeading', 'locationHeading', 'restaurantName', 'travel'], 'requires_items' => false],
            'menu-board' => ['type' => 'features', 'items_from' => 'groups', 'not_shown' => ['items.*.dishes', 'items.*.items']],
            'menu-rail' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => ['items.*.imageAlt', 'items.*.label', 'items.*.meta']],
            'private-dining' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['image', 'imageAlt', 'label', 'url'], 'requires_items' => false],
            'reservation-banner' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['bookingsUrl', 'destinationConfirmed', 'emailAddress', 'externalUrl', 'formUrl', 'phoneNumber', 'reservationDestination'], 'requires_items' => false],
            'review-wall' => ['type' => 'proof', 'items_from' => null, 'not_shown' => ['items.*.meta', 'items.*.source']],
        ],
        'events' => [
            'agenda-grid-days-tracks-rooms' => ['type' => 'features', 'items_from' => 'days', 'not_shown' => ['items.*.label', 'items.*.tracks']],
            'countdown-band' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['actionLabel', 'actionUrl', 'deadline'], 'requires_items' => false],
            'live-now-replay-state' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['actionLabel', 'actionUrl', 'state'], 'requires_items' => false],
            'past-editions-archive' => ['type' => 'features', 'items_from' => 'editions', 'not_shown' => ['items.*.stat', 'items.*.year']],
            'speaker-wall-hover-bios' => ['type' => 'proof', 'items_from' => 'speakers', 'not_shown' => ['items.*.bio', 'items.*.photo', 'items.*.role']],
            'sponsor-tier-walls' => ['type' => 'features', 'items_from' => 'tiers', 'not_shown' => ['items.*.colorToken', 'items.*.label', 'items.*.sponsors']],
            'ticket-tier-comparison' => ['type' => 'features', 'items_from' => 'tiers', 'not_shown' => ['items.*.buttonLabel', 'items.*.features', 'items.*.name', 'items.*.price', 'items.*.url']],
            'venue-travel-panels' => ['type' => 'features', 'items_from' => 'panels', 'not_shown' => ['items.*.icon', 'items.*.linkLabel', 'items.*.url']],
        ],
        'grounds' => [
            'events-billboard' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => []],
            'landmark-plot' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['building', 'emailAddress', 'formUrl', 'items', 'label', 'landmark', 'linkLabel', 'linkUrl', 'location', 'media', 'newsletterAction', 'newsletterHeading', 'plotKind', 'position', 'size', 'terrain', 'world', 'worldAnchor'], 'requires_items' => false],
            'minimap-nav' => ['type' => 'features', 'items_from' => 'landmarks', 'not_shown' => ['label', 'world', 'items.*.id', 'items.*.label']],
            'opening-hours-board' => ['type' => 'features', 'items_from' => 'hours', 'not_shown' => ['note', 'items.*.day', 'items.*.dayKey', 'items.*.time']],
            'resident-profile' => ['type' => 'proof', 'items_from' => 'facts', 'not_shown' => ['building', 'links', 'terrain', 'items.*.label', 'items.*.value']],
            'trail-story' => ['type' => 'features', 'items_from' => 'stops', 'not_shown' => ['items.*.heading']],
            'wayfinding-signpost' => ['type' => 'features', 'items_from' => 'links', 'not_shown' => ['label', 'items.*.href', 'items.*.label', 'items.*.landmark']],
        ],
        'almanac' => [
            'booking-slot-card' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['bookingsUrl', 'capacityBand', 'dateLabel', 'emailAddress', 'formUrl'], 'requires_items' => false],
            'contact' => ['type' => 'contact-split', 'items_from' => null, 'not_shown' => ['emailAddress', 'formUrl'], 'requires_items' => false],
            'countdown-next-opening' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['eyebrow', 'staticDate', 'target'], 'requires_items' => false],
            'day-ledger-session-rail' => ['type' => 'content-listing', 'items_from' => 'sessions', 'not_shown' => ['emailAddress', 'formUrl', 'items.*.bookingTarget', 'items.*.capacityBand', 'items.*.startsAt', 'items.*.time']],
            'listing' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => []],
            'month-grid-living-ledger' => ['type' => 'content-listing', 'items_from' => 'days', 'not_shown' => ['closedHeading', 'closedSeason', 'closedSummary', 'month', 'monthLabel', 'nextUrl', 'previousUrl', 'startColumn', 'items.*.annotation', 'items.*.date', 'items.*.dayNumber', 'items.*.label', 'items.*.quietSummary', 'items.*.sessions', 'items.*.weekday']],
            'next-sessions-strip' => ['type' => 'content-listing', 'items_from' => 'sessions', 'not_shown' => ['items.*.capacityBand', 'items.*.date', 'items.*.label', 'items.*.startsAt']],
            'season-archive-patina-wall' => ['type' => 'features', 'items_from' => 'seasons', 'not_shown' => ['items.*.footnote', 'items.*.label']],
            'season-band-month-scroller' => ['type' => 'features', 'items_from' => 'months', 'not_shown' => ['label', 'items.*.label', 'items.*.season', 'items.*.url']],
            'series-recurring-index' => ['type' => 'content-listing', 'items_from' => 'series', 'not_shown' => ['items.*.schedule']],
            'year-heatmap-glance' => ['type' => 'features', 'items_from' => 'months', 'not_shown' => ['items.*.count', 'items.*.heat', 'items.*.label', 'items.*.url']],
        ],
        'knowledge' => [
            'api-reference-parameter-table' => ['type' => 'agents-automation', 'items_from' => 'parameters', 'not_shown' => ['codeSample', 'items.*.required', 'items.*.type']],
            'callout-admonition-system' => ['type' => 'workflow-rails', 'items_from' => 'admonitions', 'not_shown' => ['items.*.body', 'items.*.kind']],
            'doc-tree-sidebar' => ['type' => 'content-listing', 'items_from' => 'tree', 'not_shown' => ['currentUrl', 'items.*.children']],
            'feedback-footer' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['reasons'], 'requires_items' => false],
            'in-article-toc-scroll-spy' => ['type' => 'workflow-rails', 'items_from' => null, 'not_shown' => ['items.*.anchor', 'items.*.label']],
            'search-spotlight-hero' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['artefact', 'currentUrl', 'quickLinks', 'searchAction', 'searchProvided', 'tree', 'treeHeading', 'treeVariant'], 'requires_items' => false],
            'version-changelog-surfaces' => ['type' => 'changelog-integrations', 'items_from' => 'entries', 'not_shown' => ['items.*.changeType', 'items.*.version']],
        ],
        'switchboard' => [
            'changelog-ticker' => ['type' => 'changelog-integrations', 'items_from' => null, 'not_shown' => ['items.*.date', 'items.*.dateLabel', 'items.*.note', 'items.*.url', 'items.*.version']],
            'command-board-feature' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['eyebrow', 'id'], 'requires_items' => false],
            'command-reference-table' => ['type' => 'agents-automation', 'items_from' => null, 'not_shown' => ['items.*.default', 'items.*.flag', 'items.*.type']],
            'index-card-grid' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => ['items.*.hotkey', 'items.*.keywords']],
            'keycap-legend' => ['type' => 'workflow-rails', 'items_from' => 'groups', 'not_shown' => ['items.*.items', 'items.*.label']],
            'listing' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => []],
            'man-page-hero' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['command', 'id', 'label', 'manual', 'synopsis', 'url'], 'requires_items' => false],
            'prompt-cta' => ['type' => 'cta', 'items_from' => null, 'not_shown' => ['bookingUrl', 'contactPath', 'email', 'formHandle', 'id', 'newsletter', 'prompt'], 'requires_items' => false],
            'query-recipes' => ['type' => 'workflow-rails', 'items_from' => null, 'not_shown' => ['queryUrl', 'items.*.label', 'items.*.query']],
            'status-strip' => ['type' => 'proof', 'items_from' => null, 'not_shown' => ['label', 'items.*.figure', 'items.*.trend', 'items.*.trendLabel']],
            'transcript-faq' => ['type' => 'workflow-rails', 'items_from' => null, 'not_shown' => ['items.*.answer', 'items.*.question']],
        ],
        'liquid-glass' => [
            'floating-glass-nav' => ['type' => 'navigation', 'items_from' => null, 'not_shown' => ['label']],
            'glass-feature-card' => ['type' => 'agents-automation', 'items_from' => null, 'not_shown' => ['items.*.eyebrow', 'items.*.metric', 'items.*.metricLabel', 'items.*.variant']],
            'layered-depth-hero' => ['type' => 'hero', 'items_from' => null, 'not_shown' => ['anchorId', 'frontImage', 'frontImageAlt', 'panes'], 'requires_items' => false],
            'presets' => ['type' => 'content-listing', 'items_from' => 'presets', 'not_shown' => []],
            'refraction-grid' => ['type' => 'changelog-integrations', 'items_from' => 'tiles', 'not_shown' => []],
            'showcase' => ['type' => 'content-listing', 'items_from' => null, 'not_shown' => []],
            'translucent-stat-band' => ['type' => 'proof', 'items_from' => 'stats', 'not_shown' => ['items.*.decimals', 'items.*.prefix', 'items.*.suffix']],
        ],
    ];

    /**
     * Labels a successor section prints on an item that has no value of its
     * own, so the preview can say what migrated items will start to show.
     *
     * Keyed by successor theme as well as section type: two successors can
     * both have a `content-listing` section that prints different defaults.
     *
     * @var array<string, array<string, list<string>>> successor theme key => successor section type => translation keys
     */
    private const array SUCCESSOR_DEFAULT_LABELS = [
        'portfolio' => [
            'project-feed' => ['capell-theme-portfolio::sections.project_feed.default_meta', 'capell-theme-portfolio::sections.project_feed.default_stat'],
            'related-projects' => ['capell-theme-portfolio::sections.related_projects.default_meta'],
        ],
        'editorial' => [
            'content-listing' => ['capell-theme-editorial::sections.listing.kicker'],
            'opinion-grid-with-bylines' => ['capell-theme-editorial::sections.opinion.default_meta'],
            'live-event-timeline' => ['capell-theme-editorial::sections.live.update_label'],
            'top-stories' => ['capell-theme-editorial::sections.lead.lead_label', 'capell-theme-editorial::sections.lead.brief_label'],
        ],
    ];

    /**
     * @return SectionMapping|null
     */
    public function for(string $retiredThemeKey, string $sectionType): ?array
    {
        return self::MAP[$retiredThemeKey][$sectionType] ?? null;
    }

    /**
     * @return list<string> translation keys of the labels the successor section prints on items without their own
     */
    public function successorDefaultLabels(string $successorThemeKey, string $successorSectionType): array
    {
        return self::SUCCESSOR_DEFAULT_LABELS[$successorThemeKey][$successorSectionType] ?? [];
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    public function allSuccessorDefaultLabels(): array
    {
        return self::SUCCESSOR_DEFAULT_LABELS;
    }

    /**
     * The list the successor section will read after the move: the saved
     * items, or the list the retired section kept under another key.
     *
     * @param  SectionMapping  $mapping
     * @param  array<array-key, mixed>  $meta
     * @return array<array-key, mixed>
     */
    public function successorItems(array $mapping, array $meta): array
    {
        $items = $meta['items'] ?? ($mapping['items_from'] !== null ? $meta[$mapping['items_from']] ?? null : null);

        return is_array($items) ? $items : [];
    }

    /**
     * @return array<string, array<string, SectionMapping>>
     */
    public function all(): array
    {
        return self::MAP;
    }

    /**
     * The mapped fields this widget's saved data actually carries, so the
     * preview names only what an owner would notice is no longer shown.
     *
     * @param  SectionMapping  $mapping
     * @param  array<array-key, mixed>  $meta
     * @return list<string>
     */
    public function hiddenFields(array $mapping, array $meta): array
    {
        $items = $this->successorItems($mapping, $meta);
        $hidden = [];

        foreach ($mapping['not_shown'] as $field) {
            if (str_starts_with($field, 'items.*.')) {
                $key = substr($field, strlen('items.*.'));
                $present = array_any($items, static fn (mixed $item): bool => is_array($item) && filled($item[$key] ?? null));
            } else {
                $present = filled($meta[$field] ?? null);
            }

            if ($present) {
                $hidden[] = $field;
            }
        }

        return $hidden;
    }
}
