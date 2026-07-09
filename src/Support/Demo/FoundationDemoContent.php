<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;

/**
 * Complete, vertical-authentic demo content for the Foundation theme itself.
 *
 * Foundation has no domain vocabulary of its own — its signature sections
 * are the three it uniquely ships as the shared runtime: `search`,
 * `pagination`, and `form`. Every surface is framed as a documentation/
 * starter-kit site ("Foundation Docs") so those three sections read as
 * genuine content rather than an empty scaffold.
 */
final class FoundationDemoContent implements ProvidesThemeDemoContent
{
    private const string BRAND = 'Foundation Docs';

    private const string SUPPORT_EMAIL = 'support@foundation-docs.example';

    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        $media = ThemeDemoMedia::groupedForTheme($themeKey);

        return [
            $this->homepage($themeKey, $media),
            $this->directory($themeKey, $media),
            $this->detail($themeKey, $media),
            $this->contact($themeKey, $media),
            $this->empty($themeKey, $media),
            $this->notFound($themeKey, $media),
            $this->cta($themeKey, $media),
        ];
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     */
    private function homepage(string $themeKey, array $media): ThemeDemoPageDefinition
    {
        return new ThemeDemoPageDefinition(
            surface: 'homepage',
            name: self::BRAND . ' Home',
            title: self::BRAND . ' — the starter theme every Capell site can build on',
            slug: 'theme-' . $themeKey,
            content: $this->prose(
                'Every Capell site starts on the same solid ground',
                'Foundation is the shared runtime and starter theme behind every child theme in the catalogue.',
            ),
            renderData: [
                'summary' => 'Foundation is the shared runtime, token system, and starter theme every first-party Capell theme extends — documented here as a working starter site.',
                'navigation' => $this->navigation(),
                'footer' => $this->footer(),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'The Capell starter theme',
                        heading: 'Every Capell site starts on the same solid ground',
                        summary: 'Foundation ships the design tokens, standard sections, and shared runtime every first-party theme extends — search the docs, browse the guides, or start building.',
                        primaryLabel: 'Search the docs',
                        primaryUrl: '#search',
                        secondaryLabel: 'Browse the guides',
                        secondaryUrl: '#content-listing',
                    ),
                    $this->featuresSection(
                        heading: 'What ships with Foundation',
                        summary: 'The building blocks every child theme inherits on day one.',
                    ),
                    $this->searchSection(
                        heading: 'Search the documentation',
                        summary: 'Find a guide, a token reference, or a section example by keyword.',
                    ),
                    $this->contentListingSection(
                        heading: 'Start with a guide',
                        summary: 'The most-read guides for teams building their first Capell theme.',
                        media: $media,
                    ),
                    $this->proofSection(
                        heading: 'Trusted as the base for every first-party theme',
                        summary: 'A few numbers from the catalogue Foundation underpins.',
                    ),
                    $this->paginationSection(
                        currentPage: 1,
                        totalPages: 6,
                    ),
                    $this->ctaSection(
                        heading: 'Ready to build on Foundation?',
                        summary: 'Start a new theme with the generator, or extend Foundation directly.',
                        primaryLabel: 'Read the quick start',
                        primaryUrl: '#content-listing',
                        secondaryLabel: 'Search the docs',
                        secondaryUrl: '#search',
                    ),
                ],
            ],
            type: PageTypeEnum::Home,
            layout: LayoutEnum::Home,
        );
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     */
    private function directory(string $themeKey, array $media): ThemeDemoPageDefinition
    {
        return new ThemeDemoPageDefinition(
            surface: 'directory',
            name: self::BRAND . ' Guides',
            title: 'Guides — ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-directory',
            content: $this->prose(
                'Every guide, indexed and searchable',
                'Browse the full guide library, or search by keyword to jump straight to the page you need.',
            ),
            renderData: [
                'summary' => 'Every Foundation guide in one searchable, paginated index — from first install to token customisation.',
                'navigation' => $this->navigation(),
                'footer' => $this->footer(),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'The guide index',
                        heading: 'Every guide, indexed and searchable',
                        summary: 'Search by keyword, or page through the full library below.',
                        primaryLabel: 'Search the guides',
                        primaryUrl: '#search',
                        secondaryLabel: 'Jump to page 2',
                        secondaryUrl: '#pagination',
                    ),
                    $this->searchSection(
                        heading: 'Search the guides',
                        summary: 'Type a keyword to filter the index below.',
                    ),
                    $this->contentListingSection(
                        heading: 'All guides',
                        summary: 'Page 1 of 6 — the most-read guides come first.',
                        media: $media,
                    ),
                    $this->paginationSection(
                        currentPage: 1,
                        totalPages: 6,
                    ),
                    $this->ctaSection(
                        heading: 'Can\'t find what you need?',
                        summary: 'Search the index above, or write to the docs team directly.',
                        primaryLabel: 'Search the guides',
                        primaryUrl: '#search',
                        secondaryLabel: 'Contact the docs team',
                        secondaryUrl: 'theme-' . $themeKey . '-contact',
                        secondaryIsPath: true,
                    ),
                ],
            ],
            layout: LayoutEnum::Results,
        );
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     */
    private function detail(string $themeKey, array $media): ThemeDemoPageDefinition
    {
        return new ThemeDemoPageDefinition(
            surface: 'detail',
            name: self::BRAND . ' Guide',
            title: 'Building your first section variant — ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-detail',
            content: $this->prose(
                'Building your first section variant',
                'A walkthrough of declaring a named variant for a standard Foundation section.',
            ),
            renderData: [
                'summary' => 'Building your first section variant — a walkthrough of declaring a named variant for a standard Foundation section.',
                'navigation' => $this->navigation(),
                'footer' => $this->footer(),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Guide · Theming',
                        heading: 'Building your first section variant',
                        summary: 'Foundation\'s hero, content-listing, and cta sections each support 2–3 named variants out of the box. This guide walks through declaring your own.',
                        primaryLabel: 'Fill in the request form',
                        primaryUrl: '#form',
                        secondaryLabel: 'Back to all guides',
                        secondaryUrl: 'theme-' . $themeKey . '-directory',
                        secondaryIsPath: true,
                        media: $media,
                        mediaKey: 'detail',
                        mediaAlt: 'A code editor open on a Blade section variant file',
                    ),
                    $this->featuresSection(
                        heading: 'What this guide covers',
                        summary: 'Three steps from a blank Blade view to a working variant.',
                    ),
                    $this->formSection(
                        heading: 'Request a deeper walkthrough',
                        summary: 'Tell us which section you\'re customising and we\'ll follow up with worked examples.',
                    ),
                    $this->ctaSection(
                        heading: 'Keep reading the theming guides',
                        summary: 'This guide is part of a longer series on Foundation\'s token and section system.',
                        primaryLabel: 'Browse all guides',
                        primaryUrl: 'theme-' . $themeKey . '-directory',
                        primaryIsPath: true,
                        secondaryLabel: 'Search the docs',
                        secondaryUrl: '#search',
                    ),
                ],
            ],
        );
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     */
    private function contact(string $themeKey, array $media): ThemeDemoPageDefinition
    {
        return new ThemeDemoPageDefinition(
            surface: 'contact',
            name: self::BRAND . ' Contact',
            title: 'Talk to the docs team — ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-contact',
            content: $this->prose(
                'Talk to the docs team',
                'Ask a theming question, report a gap in the guides, or request a new example.',
            ),
            renderData: [
                'summary' => 'Ask a theming question, report a gap in the guides, or request a new example — every message reaches the docs team.',
                'navigation' => $this->navigation(),
                'footer' => $this->footer(),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Contact the docs team',
                        heading: 'Talk to the docs team',
                        summary: 'Fill in the form below, or write to ' . self::SUPPORT_EMAIL . ' directly. We reply within two business days.',
                        primaryLabel: 'Fill in the form',
                        primaryUrl: '#form',
                        secondaryLabel: 'Email us instead',
                        secondaryUrl: 'mailto:' . self::SUPPORT_EMAIL,
                    ),
                    $this->formSection(
                        heading: 'Send us a question',
                        summary: 'Tell us what you\'re building and where the docs fell short.',
                    ),
                    $this->featuresSection(
                        heading: 'Other ways to reach us',
                        summary: 'Pick whichever channel fits the question.',
                    ),
                    $this->ctaSection(
                        heading: 'Prefer to search first?',
                        summary: 'Most questions are already answered somewhere in the guide index.',
                        primaryLabel: 'Search the docs',
                        primaryUrl: '#search',
                        secondaryLabel: 'Browse all guides',
                        secondaryUrl: 'theme-' . $themeKey . '-directory',
                        secondaryIsPath: true,
                    ),
                ],
            ],
            layout: LayoutEnum::System,
        );
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     */
    private function empty(string $themeKey, array $media): ThemeDemoPageDefinition
    {
        return new ThemeDemoPageDefinition(
            surface: 'empty',
            name: self::BRAND . ' No Results',
            title: 'No guides match that search — ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-empty',
            content: $this->prose(
                'No guides match that search',
                'A composed empty state for a filtered guide search with no matching results.',
            ),
            renderData: [
                'summary' => 'No guides match that search yet — try a broader keyword or browse the full index instead.',
                'navigation' => $this->navigation(),
                'footer' => $this->footer(),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Search results',
                        heading: 'No guides match that search',
                        summary: 'Try a broader keyword, or clear the search to browse the full guide index below.',
                        primaryLabel: 'Browse all guides',
                        primaryUrl: 'theme-' . $themeKey . '-directory',
                        primaryIsPath: true,
                        secondaryLabel: 'Back to the homepage',
                        secondaryUrl: 'theme-' . $themeKey,
                        secondaryIsPath: true,
                    ),
                    $this->searchSection(
                        heading: 'Try another search',
                        summary: 'Search by section name, token, or feature.',
                    ),
                    $this->ctaSection(
                        heading: 'Still can\'t find it?',
                        summary: 'Send the docs team a note and we\'ll point you in the right direction.',
                        primaryLabel: 'Contact the docs team',
                        primaryUrl: 'theme-' . $themeKey . '-contact',
                        primaryIsPath: true,
                        secondaryLabel: 'Browse all guides',
                        secondaryUrl: 'theme-' . $themeKey . '-directory',
                        secondaryIsPath: true,
                    ),
                ],
            ],
        );
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     */
    private function notFound(string $themeKey, array $media): ThemeDemoPageDefinition
    {
        return new ThemeDemoPageDefinition(
            surface: 'not-found',
            name: self::BRAND . ' 404',
            title: 'That page could not be found — ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-404',
            content: $this->prose(
                'That page could not be found',
                'A not-found page that routes readers back into search and the guide index.',
            ),
            renderData: [
                'summary' => 'That page has moved or never existed — search the docs or head back to the guide index.',
                'navigation' => $this->navigation(),
                'footer' => $this->footer(),
                'sections' => [
                    $this->heroSection(
                        eyebrow: '404',
                        heading: 'That page could not be found',
                        summary: 'The link is broken or the guide moved. Search the docs, or head back to the homepage.',
                        primaryLabel: 'Search the docs',
                        primaryUrl: '#search',
                        secondaryLabel: 'Back to the homepage',
                        secondaryUrl: 'theme-' . $themeKey,
                        secondaryIsPath: true,
                    ),
                    $this->ctaSection(
                        heading: 'Find your way back',
                        summary: 'Browse the full guide index, or search for the page you were looking for.',
                        primaryLabel: 'Browse all guides',
                        primaryUrl: 'theme-' . $themeKey . '-directory',
                        primaryIsPath: true,
                        secondaryLabel: 'Search the docs',
                        secondaryUrl: '#search',
                    ),
                ],
            ],
            type: PageTypeEnum::NotFound,
            layout: LayoutEnum::System,
        );
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     */
    private function cta(string $themeKey, array $media): ThemeDemoPageDefinition
    {
        return new ThemeDemoPageDefinition(
            surface: 'cta',
            name: self::BRAND . ' Get Started',
            title: 'Start your first Capell theme — ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-cta',
            content: $this->prose(
                'Start your first Capell theme today',
                'A focused conversion page inviting builders to scaffold a new theme on Foundation.',
            ),
            renderData: [
                'summary' => 'Start your first Capell theme today — scaffold a new package on Foundation in minutes.',
                'navigation' => $this->navigation(),
                'footer' => $this->footer(),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Get started',
                        heading: 'Start your first Capell theme today',
                        summary: 'Run the generator to scaffold a new theme package on Foundation, complete with manifest, seeded pages, and tests.',
                        primaryLabel: 'Request early access',
                        primaryUrl: '#form',
                        secondaryLabel: 'Read the quick start',
                        secondaryUrl: 'theme-' . $themeKey . '-directory',
                        secondaryIsPath: true,
                    ),
                    $this->featuresSection(
                        heading: 'What you get out of the box',
                        summary: 'Everything a new theme inherits from Foundation on day one.',
                    ),
                    $this->formSection(
                        heading: 'Request early access',
                        summary: 'Tell us what you\'re building and we\'ll get you set up.',
                    ),
                    $this->ctaSection(
                        heading: 'Prefer to read first?',
                        summary: 'Browse the full guide index before you start scaffolding.',
                        primaryLabel: 'Browse all guides',
                        primaryUrl: 'theme-' . $themeKey . '-directory',
                        primaryIsPath: true,
                        secondaryLabel: 'Search the docs',
                        secondaryUrl: '#search',
                    ),
                ],
            ],
        );
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     * @return array<string, mixed>
     */
    private function heroSection(
        string $eyebrow,
        string $heading,
        string $summary,
        string $primaryLabel,
        string $primaryUrl,
        string $secondaryLabel,
        string $secondaryUrl,
        bool $primaryIsPath = false,
        bool $secondaryIsPath = false,
        ?array $media = null,
        ?string $mediaKey = null,
        ?string $mediaAlt = null,
    ): array {
        $section = [
            'type' => 'hero',
            'eyebrow' => $eyebrow,
            'heading' => $heading,
            'summary' => $summary,
            'actions' => [
                ['label' => $primaryLabel, 'url' => $primaryIsPath ? '/' . $primaryUrl : $primaryUrl, 'style' => 'primary'],
                ['label' => $secondaryLabel, 'url' => $secondaryIsPath ? '/' . $secondaryUrl : $secondaryUrl, 'style' => 'secondary'],
            ],
        ];

        if (is_array($media) && is_string($mediaKey)) {
            $section['mediaUrl'] = $media[$mediaKey][0] ?? $media['hero'][0];
            $section['mediaAlt'] = $mediaAlt;
        }

        return $section;
    }

    /**
     * @return array<string, mixed>
     */
    private function searchSection(string $heading, string $summary): array
    {
        return [
            'type' => 'search',
            'heading' => $heading,
            'summary' => $summary,
            'action' => '/search',
            'placeholder' => 'Search guides, tokens, and sections…',
            'results' => [
                ['title' => 'Declaring a section variant', 'summary' => 'How to add a new named variant to a standard section.'],
                ['title' => 'Theme Studio token reference', 'summary' => 'Every colour, spacing, and motion token Foundation exposes.'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paginationSection(int $currentPage, int $totalPages): array
    {
        return [
            'type' => 'pagination',
            'heading' => 'More guides',
            'summary' => 'Page ' . $currentPage . ' of ' . $totalPages . '.',
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'baseUrl' => '/guides',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formSection(string $heading, string $summary): array
    {
        return [
            'type' => 'form',
            'heading' => $heading,
            'summary' => $summary,
            'action' => '/contact',
            'fields' => [
                ['type' => 'text', 'name' => 'name', 'label' => 'Your name', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email address', 'required' => true],
                ['type' => 'select', 'name' => 'topic', 'label' => 'Topic', 'options' => ['Theming', 'Layout Builder', 'Something else']],
                ['type' => 'textarea', 'name' => 'message', 'label' => 'Message', 'required' => true],
            ],
            'submitLabel' => 'Send message',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function featuresSection(string $heading, string $summary): array
    {
        return [
            'type' => 'features',
            'heading' => $heading,
            'summary' => $summary,
            'items' => [
                ['title' => 'A full token system', 'summary' => 'Colour, spacing, radius, heading scale, and motion tokens every child theme inherits.'],
                ['title' => 'Ten standard sections', 'summary' => 'Navigation, hero, features, proof, content-listing, search, pagination, form, cta, and footer — ready to extend.'],
                ['title' => 'Shared JS modules', 'summary' => 'Carousel, lightbox, tabs, count-up, scroll-spy, compare-slider, and accordion, opt-in and reduced-motion aware.'],
            ],
        ];
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     * @return array<string, mixed>
     */
    private function contentListingSection(string $heading, string $summary, array $media): array
    {
        $images = array_values(array_unique(array_merge($media['listing'], $media['proof'])));

        $entries = [
            ['title' => 'Quick start: your first Foundation-based theme', 'summary' => 'From `capell:make-theme` to a first render, in under ten minutes.'],
            ['title' => 'Working with Theme Studio tokens', 'summary' => 'How colour, spacing, and motion tokens flow from settings into CSS custom properties.'],
            ['title' => 'The ten standard sections', 'summary' => 'What each standard section expects in its render-data payload.'],
        ];

        $items = [];

        foreach ($entries as $index => $entry) {
            $items[] = [
                ...$entry,
                'image' => $images[$index % max(count($images), 1)] ?? null,
                'imageAlt' => $entry['title'],
            ];
        }

        return [
            'type' => 'content-listing',
            'heading' => $heading,
            'summary' => $summary,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function proofSection(string $heading, string $summary): array
    {
        return [
            'type' => 'proof',
            'heading' => $heading,
            'summary' => $summary,
            'items' => [
                ['title' => '19 catalogue themes', 'summary' => 'Every first-party theme extends Foundation for its runtime and tokens.'],
                ['title' => '10 standard sections', 'summary' => 'A shared vocabulary every child theme can rely on.'],
                ['title' => '~16KB shared JS', 'summary' => 'Opt-in carousel, lightbox, tabs, and more — within a strict fleet-wide budget.'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ctaSection(
        string $heading,
        string $summary,
        string $primaryLabel,
        string $primaryUrl,
        string $secondaryLabel,
        string $secondaryUrl,
        bool $primaryIsPath = false,
        bool $secondaryIsPath = false,
    ): array {
        return [
            'type' => 'cta',
            'heading' => $heading,
            'summary' => $summary,
            'actions' => [
                ['label' => $primaryLabel, 'url' => $primaryIsPath ? '/' . $primaryUrl : $primaryUrl, 'style' => 'primary'],
                ['label' => $secondaryLabel, 'url' => $secondaryIsPath ? '/' . $secondaryUrl : $secondaryUrl, 'style' => 'secondary'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function navigation(): array
    {
        return [
            'brandName' => self::BRAND,
            'items' => [
                ['label' => 'Guides', 'url' => '/'],
                ['label' => 'Search', 'url' => '/'],
                ['label' => 'Tokens', 'url' => '/'],
                ['label' => 'Contact', 'url' => '/'],
            ],
            'ctaLabel' => 'Get started',
            'ctaUrl' => '/',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function footer(): array
    {
        return [
            'brandName' => self::BRAND,
            'summary' => 'The shared runtime, token system, and starter theme every first-party Capell theme extends.',
            'columns' => [
                [
                    'heading' => 'Docs',
                    'links' => [
                        ['label' => 'Guide index', 'url' => '/'],
                        ['label' => 'Token reference', 'url' => '/'],
                    ],
                ],
                [
                    'heading' => 'Support',
                    'links' => [
                        ['label' => 'Contact the docs team', 'url' => 'mailto:' . self::SUPPORT_EMAIL],
                        ['label' => self::SUPPORT_EMAIL, 'url' => 'mailto:' . self::SUPPORT_EMAIL],
                    ],
                ],
            ],
        ];
    }

    private function prose(string $heading, string $summary): string
    {
        return sprintf('<h2>%s</h2><p>%s</p>', e($heading), e($summary));
    }
}
