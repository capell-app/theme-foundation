<?php

declare(strict_types=1);

use Capell\Tests\Support\RenderedDocument;

/*
 * Guards the Wave 5 section-variant contract: a theme may declare
 * `frontend['sectionVariants']` (see VariantViewSectionRenderer) naming which
 * variants a section supports. Every declared variant must resolve to a real
 * Blade view on disk, named `<section>--<variant>.blade.php` beside the base
 * `<section>.blade.php`, so a declared variant can never silently 404 into
 * the renderer's fallback markup.
 *
 * Phase C: a theme converted to render through x-capell::layout +
 * layout-builder has no sections/ directory and declares no sectionVariants,
 * so it is naturally skipped by the `! is_array($sectionVariants)` guard
 * below — no explicit exemption is needed for it.
 *
 * Wave 2.2: theme-foundation itself is the one theme whose section views
 * predate the `resources/views/sections/` convention used by every child
 * theme — its own base/variant Blade lives under
 * `resources/views/theme/sections/` instead (see
 * FoundationThemeServiceProvider::themeStudioSectionRenderers()). The path
 * candidates below check both locations so this single generic test covers
 * Foundation's own declared variants as well as every child theme's.
 */

require_once __DIR__ . '/../../../../tests/Packages/Support/ThemeManifestContracts.php';
require_once __DIR__ . '/../Support/DomQueries.php';

/**
 * @return array<string, array{directory: string, providerClass: class-string}>
 */
function sectionVariantThemeProviders(): array
{
    $manifests = capell_theme_manifest_entries();
    $providers = [];

    foreach ($manifests as $entry) {
        $providerClasses = capell_theme_manifest_provider_classes($entry['manifest'], $entry['directory']);
        $providerClass = $providerClasses[0] ?? null;

        if ($providerClass === null || ! method_exists($providerClass, 'definition')) {
            continue;
        }

        $themeKey = $entry['manifest']['themeKey'] ?? null;

        if (! is_string($themeKey) || $themeKey === '') {
            continue;
        }

        $providers[$themeKey] = [
            'directory' => $entry['directory'],
            'providerClass' => $providerClass,
        ];
    }

    return $providers;
}

it('resolves every declared section variant to a real Blade view file', function (): void {
    $checked = 0;

    foreach (sectionVariantThemeProviders() as $themeKey => $entry) {
        /** @var class-string $providerClass */
        $providerClass = $entry['providerClass'];
        $definition = $providerClass::definition();
        $sectionVariants = $definition->frontend['sectionVariants'] ?? null;

        if (! is_array($sectionVariants)) {
            continue;
        }

        foreach ($sectionVariants as $section => $variants) {
            expect($variants)->toBeArray();

            if (! is_iterable($variants) || ! is_string($section)) {
                continue;
            }

            foreach ($variants as $variant) {
                if (! is_string($variant) || $variant === 'default') {
                    continue;
                }

                $checked++;
                $candidatePaths = [
                    $entry['directory'] . "/resources/views/sections/{$section}--{$variant}.blade.php",
                    $entry['directory'] . "/resources/views/theme/sections/{$section}--{$variant}.blade.php",
                ];
                $resolvedViewExists = collect($candidatePaths)->contains(static fn (string $candidatePath): bool => is_file($candidatePath));

                expect($resolvedViewExists)
                    ->toBeTrue(sprintf(
                        'Theme [%s] declares variant [%s] for section [%s] but none of [%s] exist.',
                        $themeKey,
                        $variant,
                        $section,
                        implode(', ', $candidatePaths),
                    ));
            }
        }
    }

    expect($checked)->toBeGreaterThan(0);
});

it('ships every Foundation hero variant as a subordinate heading with stable media and premium interaction states', function (): void {
    $themeRoot = dirname(__DIR__, 2);
    $heroViews = [
        'hero.blade.php',
        'hero--split.blade.php',
        'hero--stacked.blade.php',
        'hero--full-bleed.blade.php',
    ];

    foreach ($heroViews as $heroView) {
        $html = view()->file($themeRoot . '/resources/views/theme/sections/' . $heroView, ['section' => (object) [
            'eyebrow' => 'Introduction', 'heading' => 'A subordinate hero', 'summary' => 'Helpful copy',
            'mediaUrl' => '/media/hero.jpg', 'mediaAlt' => 'Hero photograph',
            'actions' => [['url' => '/contact', 'label' => 'Contact us']],
        ]])->render();
        expect(domQueryCount($html, '//h2[normalize-space(.)="A subordinate hero"]'))->toBe(1)
            ->and(domQueryCount($html, '//h1'))->toBe(0)
            ->and(domQueryCount($html, '//img[@src="/media/hero.jpg"][@alt="Hero photograph"][number(@width) > 0][number(@height) > 0][@loading="eager"][@fetchpriority="high"]'))->toBe(1)
            ->and(domQueryCount($html, '//a[@href="/contact"][normalize-space(.)="Contact us"]'))->toBe(1);
    }

    $styles = file_get_contents($themeRoot . '/resources/css/theme/theme.css');

    expect($styles)
        ->toContain('.theme-hero a:focus-visible')
        ->toContain('.theme-hero--full-bleed::after')
        ->toContain('calc(100svh - var(--header-height, 4.7rem))')
        ->toContain('@media (max-width: 767px)')
        ->toContain('.dark .theme-hero');
});

it('ships proof and conversion sections with resilient media and action states', function (): void {
    $themeRoot = dirname(__DIR__, 2);
    $styles = file_get_contents($themeRoot . '/resources/css/theme/theme.css');
    $features = view('capell-theme-foundation::theme.sections.features', ['section' => (object) [
        'heading' => 'Features', 'summary' => '',
        'features' => [['title' => 'Feature title', 'image' => '/media/feature.jpg']],
    ]])->render();
    $proof = view('capell-theme-foundation::theme.sections.proof', ['section' => (object) [
        'heading' => 'Proof', 'summary' => '',
        'items' => [['title' => 'Proof title', 'image' => '/media/proof.jpg']],
    ]])->render();
    expect(domQueryCount($features, '//img[@src="/media/feature.jpg"][@alt="Feature title"][@width="800"][@height="480"][@loading="lazy"][@decoding="async"]'))->toBe(1)
        ->and(domQueryCount($proof, '//img[@src="/media/proof.jpg"][@alt="Proof title"][@width="800"][@height="320"][@loading="lazy"][@decoding="async"]'))->toBe(1);

    expect($styles)
        ->toContain('.theme-features article:hover')
        ->toContain('.theme-proof figure')
        ->toContain('.theme-stats-display-band .count-up-stat')
        ->toContain('.theme-cta a:focus-visible')
        ->toContain('@media (prefers-reduced-motion: reduce)');
});

it('renders discovery search with a labelled query and navigable results', function (): void {
    $document = new RenderedDocument(view('capell-theme-foundation::theme.sections.search', ['section' => (object) [
        'heading' => 'Find a guide', 'query' => 'Widgets', 'queryParameter' => 'term', 'action' => '/search',
        'results' => [['title' => 'Widget guide', 'url' => '/guides/widgets', 'summary' => 'Build a useful page.']],
    ]])->render());
    $input = $document->element('//input[@type="search"]');
    expect($input->getAttribute('name'))->toBe('term')
        ->and($input->getAttribute('value'))->toBe('Widgets')
        ->and($document->elements('//label[@for="' . $input->getAttribute('id') . '"]'))->toHaveCount(1)
        ->and($document->element('//form[@role="search"]')->getAttribute('action'))->toBe('/search')
        ->and($document->element('//form')->getAttribute('method'))->toBe('get')
        ->and($document->elements('//a[@href="/guides/widgets"]'))->toHaveCount(1)
        ->and($document->element('//h2')->textContent)->toContain('Find a guide');

    $pagination = new RenderedDocument(view('capell-theme-foundation::theme.sections.pagination', ['section' => (object) [
        'currentPage' => 2, 'totalPages' => 3, 'baseUrl' => '/search?term=Widgets',
    ]])->render());
    expect($pagination->element('//a[@aria-current="page"]')->getAttribute('href'))->toBe('/search?term=Widgets&page=2')
        ->and($pagination->element('//a[@rel="prev"]')->getAttribute('href'))->toBe('/search?term=Widgets&page=1')
        ->and($pagination->element('//a[@rel="next"]')->getAttribute('href'))->toBe('/search?term=Widgets&page=3');
});

it('ships form and decision sections with complete accessible interaction styling', function (): void {
    $themeRoot = dirname(__DIR__, 2);
    $contact = view()->file($themeRoot . '/resources/views/theme/sections/contact-split.blade.php', [
        'section' => (object) ['heading' => 'Contact the studio', 'summary' => 'Send an enquiry.', 'address_lines' => ['Studio House', 'London']],
        'formDeliveryFragmentUrl' => '/fragments/contact-form',
    ])->render();
    $styles = file_get_contents($themeRoot . '/resources/css/theme/theme.css');

    expect(domQueryCount($contact, '//h2[normalize-space(.)="Contact the studio"]'))->toBe(1)
        ->and(domQueryCount($contact, '//address/div[normalize-space(.)="Studio House"]'))->toBe(1)
        ->and(domQueryCount($contact, '//*[@data-deferred-fragment-url="/fragments/contact-form"]'))->toBe(1)
        ->and(domQueryCount($contact, '//form'))->toBe(0);

    expect($styles)
        ->toContain('.theme-form form')
        ->toContain('.theme-contact-split form')
        ->toContain(':where(input, select, textarea):focus-visible')
        ->toContain('.theme-faq-search-discovery details[open]')
        ->toContain('.theme-faq-search-discovery summary')
        ->toContain('.theme-pricing-value-spectrum .pricing-spectrum-slider')
        ->toContain('accent-color: var(--theme-primary)');
});
