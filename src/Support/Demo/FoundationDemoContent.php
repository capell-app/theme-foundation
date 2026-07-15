<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;

/**
 * A complete general-purpose site that demonstrates Foundation without
 * exposing theme implementation language to prospective buyers.
 */
final class FoundationDemoContent implements ProvidesThemeDemoContent
{
    private const string BRAND = 'Field Office';

    private const string SUPPORT_EMAIL = 'hello@fieldoffice.example';

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
            title: self::BRAND . ' | Practical places, plainly made',
            slug: 'theme-' . $themeKey,
            content: $this->prose(
                'Practical places, plainly made',
                'We help small organisations turn overlooked spaces into useful, welcoming places.',
            ),
            renderData: [
                'summary' => 'Field Office is a small research and design practice for community spaces, shared workshops, and useful public rooms.',
                'navigation' => $this->navigation($themeKey),
                'footer' => $this->footer($themeKey),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Research and spatial practice',
                        heading: 'Practical places, plainly made',
                        summary: 'We help small organisations understand a place, agree what matters, and make the next useful change.',
                        primaryLabel: 'Start a conversation',
                        primaryUrl: 'theme-' . $themeKey . '-contact',
                        primaryIsPath: true,
                        secondaryLabel: 'Explore our field notes',
                        secondaryUrl: '#content-listing',
                    ),
                    $this->featuresSection(
                        heading: 'What we work on',
                        summary: 'Focused support for teams making shared places work harder and feel better.',
                    ),
                    $this->searchSection(
                        heading: 'Find a useful field note',
                        summary: 'Search our practical notes on briefs, walk-throughs, workshops, and handovers.',
                    ),
                    $this->contentListingSection(
                        heading: 'Notes from the field',
                        summary: 'Short, practical guidance drawn from the questions we hear most often.',
                        media: $media,
                    ),
                    $this->proofSection(
                        heading: 'A clear way of working',
                        summary: 'Enough structure to keep a project moving, without turning it into a process exercise.',
                    ),
                    $this->paginationSection(
                        currentPage: 1,
                        totalPages: 4,
                    ),
                    $this->ctaSection(
                        heading: 'Have a place with potential?',
                        summary: 'Tell us what is changing, who the place is for, and what needs to work better.',
                        primaryLabel: 'Tell us about it',
                        primaryUrl: 'theme-' . $themeKey . '-contact',
                        primaryIsPath: true,
                        secondaryLabel: 'See how we work',
                        secondaryUrl: '#proof',
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
            name: self::BRAND . ' Field Notes',
            title: 'Field notes | ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-directory',
            content: $this->prose(
                'Useful notes for shared places',
                'Browse practical guidance for planning, testing, adapting, and caring for community spaces.',
            ),
            renderData: [
                'summary' => 'A searchable collection of practical notes for teams responsible for shared spaces.',
                'navigation' => $this->navigation($themeKey),
                'footer' => $this->footer($themeKey),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Field notes',
                        heading: 'Useful notes for shared places',
                        summary: 'Search by topic or browse the complete collection, from the first walk-through to a useful handover.',
                        primaryLabel: 'Search the notes',
                        primaryUrl: '#search',
                        secondaryLabel: 'Discuss a project',
                        secondaryUrl: 'theme-' . $themeKey . '-contact',
                        secondaryIsPath: true,
                    ),
                    $this->searchSection(
                        heading: 'Search the field notes',
                        summary: 'Try a place type, project stage, or practical question.',
                    ),
                    $this->contentListingSection(
                        heading: 'All field notes',
                        summary: 'The newest notes appear first. Every note is written to be used, shared, and adapted.',
                        media: $media,
                    ),
                    $this->paginationSection(
                        currentPage: 1,
                        totalPages: 4,
                    ),
                    $this->ctaSection(
                        heading: 'Need a note we have not written?',
                        summary: 'Send us the question. If a useful answer already exists, we will point you to it.',
                        primaryLabel: 'Search the notes',
                        primaryUrl: '#search',
                        secondaryLabel: 'Ask Field Office',
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
            name: self::BRAND . ' Field Note',
            title: 'A practical brief for a shared workshop | ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-detail',
            content: $this->prose(
                'A practical brief for a shared workshop',
                'Start with the people, activities, constraints, and decisions the space needs to support.',
            ),
            renderData: [
                'summary' => 'A short guide to writing a workshop brief that helps a team make useful decisions without fixing the answer too early.',
                'navigation' => $this->navigation($themeKey),
                'footer' => $this->footer($themeKey),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Field note',
                        heading: 'A practical brief for a shared workshop',
                        summary: 'A useful brief names the decisions ahead, the people involved, and the evidence the team still needs.',
                        primaryLabel: 'Discuss your brief',
                        primaryUrl: '#form',
                        secondaryLabel: 'Back to all field notes',
                        secondaryUrl: 'theme-' . $themeKey . '-directory',
                        secondaryIsPath: true,
                        media: $media,
                        mediaKey: 'detail',
                        mediaAlt: 'A shared worktable prepared for a planning session',
                    ),
                    $this->featuresSection(
                        heading: 'Three things the brief should make clear',
                        summary: 'Keep it short enough to use and specific enough to guide the next decision.',
                    ),
                    $this->formSection(
                        heading: 'Bring us your working brief',
                        summary: 'Share the project stage and the decision that feels hardest to make next.',
                    ),
                    $this->ctaSection(
                        heading: 'Keep exploring the field notes',
                        summary: 'Find practical guidance for site visits, workshops, prototypes, and handovers.',
                        primaryLabel: 'Browse all field notes',
                        primaryUrl: 'theme-' . $themeKey . '-directory',
                        primaryIsPath: true,
                        secondaryLabel: 'Search the collection',
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
            title: 'Tell us about the place | ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-contact',
            content: $this->prose(
                'Tell us about the place',
                'Share what is changing, who uses the space, and what needs to work better.',
            ),
            renderData: [
                'summary' => 'A simple first conversation for teams considering a new brief, a workshop, or a small spatial change.',
                'navigation' => $this->navigation($themeKey),
                'footer' => $this->footer($themeKey),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Project enquiries',
                        heading: 'Tell us about the place',
                        summary: 'Use the form below or write to ' . self::SUPPORT_EMAIL . '. A short, unfinished outline is enough to begin.',
                        primaryLabel: 'Start the enquiry',
                        primaryUrl: '#form',
                        secondaryLabel: 'Write by email',
                        secondaryUrl: 'mailto:' . self::SUPPORT_EMAIL,
                    ),
                    $this->formSection(
                        heading: 'Start with what you know',
                        summary: 'Tell us about the place, the people involved, and the next decision ahead.',
                    ),
                    $this->featuresSection(
                        heading: 'What happens next',
                        summary: 'A calm first step, with no requirement for a finished brief.',
                    ),
                    $this->ctaSection(
                        heading: 'Looking for practical guidance first?',
                        summary: 'The field notes cover many of the questions that come up before a project begins.',
                        primaryLabel: 'Search the field notes',
                        primaryUrl: '#search',
                        secondaryLabel: 'Browse all notes',
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
            title: 'No field notes match that search | ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-empty',
            content: $this->prose(
                'No field notes match that search',
                'Try a broader topic, clear the search, or send us the question directly.',
            ),
            renderData: [
                'summary' => 'No field notes match that search. Try a broader topic or browse the complete collection.',
                'navigation' => $this->navigation($themeKey),
                'footer' => $this->footer($themeKey),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Search results',
                        heading: 'No field notes match that search',
                        summary: 'Try a place type, a project stage, or a broader practical question.',
                        primaryLabel: 'Browse all field notes',
                        primaryUrl: 'theme-' . $themeKey . '-directory',
                        primaryIsPath: true,
                        secondaryLabel: 'Back to the homepage',
                        secondaryUrl: 'theme-' . $themeKey,
                        secondaryIsPath: true,
                    ),
                    $this->searchSection(
                        heading: 'Try another search',
                        summary: 'Search by place, activity, project stage, or decision.',
                    ),
                    $this->ctaSection(
                        heading: 'Still looking for an answer?',
                        summary: 'Send us the question. We will share a useful starting point if we have one.',
                        primaryLabel: 'Ask Field Office',
                        primaryUrl: 'theme-' . $themeKey . '-contact',
                        primaryIsPath: true,
                        secondaryLabel: 'Browse all notes',
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
            title: 'That page could not be found | ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-404',
            content: $this->prose(
                'That page could not be found',
                'The link may be out of date. Search the field notes or return to the homepage.',
            ),
            renderData: [
                'summary' => 'That page may have moved. Search the field notes or return to a familiar starting point.',
                'navigation' => $this->navigation($themeKey),
                'footer' => $this->footer($themeKey),
                'sections' => [
                    $this->heroSection(
                        eyebrow: '404',
                        heading: 'That page could not be found',
                        summary: 'The link may be out of date. Search the field notes or head back to the homepage.',
                        primaryLabel: 'Search the field notes',
                        primaryUrl: '#search',
                        secondaryLabel: 'Back to the homepage',
                        secondaryUrl: 'theme-' . $themeKey,
                        secondaryIsPath: true,
                    ),
                    $this->ctaSection(
                        heading: 'Find your way back',
                        summary: 'Browse the complete collection or tell us what you were hoping to find.',
                        primaryLabel: 'Browse all field notes',
                        primaryUrl: 'theme-' . $themeKey . '-directory',
                        primaryIsPath: true,
                        secondaryLabel: 'Ask Field Office',
                        secondaryUrl: 'theme-' . $themeKey . '-contact',
                        secondaryIsPath: true,
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
            name: self::BRAND . ' Start A Project',
            title: 'Start with a site conversation | ' . self::BRAND,
            slug: 'theme-' . $themeKey . '-cta',
            content: $this->prose(
                'Start with a site conversation',
                'Bring the context, the people involved, and the decision that needs to become clearer.',
            ),
            renderData: [
                'summary' => 'A focused first step for organisations planning a shared space, workshop, or practical change.',
                'navigation' => $this->navigation($themeKey),
                'footer' => $this->footer($themeKey),
                'sections' => [
                    $this->heroSection(
                        eyebrow: 'Start a project',
                        heading: 'Start with a site conversation',
                        summary: 'We begin with the place as it is, the people who use it, and the next decision that matters.',
                        primaryLabel: 'Tell us about the place',
                        primaryUrl: '#form',
                        secondaryLabel: 'Read the field notes',
                        secondaryUrl: 'theme-' . $themeKey . '-directory',
                        secondaryIsPath: true,
                    ),
                    $this->featuresSection(
                        heading: 'A useful first phase',
                        summary: 'Clear outputs that help a team agree what to do next.',
                    ),
                    $this->formSection(
                        heading: 'Tell us what is changing',
                        summary: 'An outline is enough. Share the place, the people, and the decision ahead.',
                    ),
                    $this->ctaSection(
                        heading: 'Prefer to explore first?',
                        summary: 'Browse practical notes from earlier site visits, workshops, and handovers.',
                        primaryLabel: 'Browse all field notes',
                        primaryUrl: 'theme-' . $themeKey . '-directory',
                        primaryIsPath: true,
                        secondaryLabel: 'Search the notes',
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
            'placeholder' => 'Search field notes and projects',
            'results' => [
                ['title' => 'A practical brief for a shared workshop', 'summary' => 'Frame the people, activities, constraints, and decisions before fixing the answer.'],
                ['title' => 'Planning a useful first walk-through', 'summary' => 'A short set of prompts for looking, listening, and recording what matters.'],
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
            'heading' => 'More field notes',
            'summary' => 'Page ' . $currentPage . ' of ' . $totalPages . '.',
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'baseUrl' => '/field-notes',
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
                ['type' => 'select', 'name' => 'topic', 'label' => 'What would you like to discuss?', 'options' => ['A new brief', 'A workshop', 'A field note', 'Something else']],
                ['type' => 'textarea', 'name' => 'message', 'label' => 'Tell us about the place', 'required' => true],
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
                ['title' => 'Understand the place', 'summary' => 'Walk, listen, map what is already useful, and notice where everyday activity gets harder than it should.'],
                ['title' => 'Make decisions together', 'summary' => 'Turn competing needs into a brief that gives the team a clear way to choose.'],
                ['title' => 'Test the smallest useful change', 'summary' => 'Use drawings, mock-ups, and short trials before committing time and budget too early.'],
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
            ['title' => 'A practical brief for a shared workshop', 'summary' => 'Start with the people, activities, constraints, and decisions the room needs to support.'],
            ['title' => 'Planning a useful first walk-through', 'summary' => 'A compact set of prompts for looking, listening, and recording what matters.'],
            ['title' => 'Questions to ask before the brief', 'summary' => 'Surface assumptions early and give every stakeholder a clearer role in the next decision.'],
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
                ['title' => 'An open brief', 'summary' => 'A short working document that records what matters without pretending every answer is known.'],
                ['title' => 'A visible decision trail', 'summary' => 'Simple records help new voices join the work and keep earlier choices understandable.'],
                ['title' => 'A practical handover', 'summary' => 'The people caring for the place receive useful drawings, priorities, and next steps.'],
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
    private function navigation(string $themeKey): array
    {
        $homeUrl = '/theme-' . $themeKey;

        return [
            'brandName' => self::BRAND,
            'items' => [
                ['label' => 'Work', 'url' => $homeUrl . '#features'],
                ['label' => 'Field notes', 'url' => $homeUrl . '-directory'],
                ['label' => 'Approach', 'url' => $homeUrl . '#proof'],
                ['label' => 'Contact', 'url' => $homeUrl . '-contact'],
            ],
            'ctaLabel' => 'Start a project',
            'ctaUrl' => $homeUrl . '-contact',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function footer(string $themeKey): array
    {
        $homeUrl = '/theme-' . $themeKey;

        return [
            'brandName' => self::BRAND,
            'summary' => 'Research and practical design support for community spaces, shared workshops, and useful public rooms.',
            'columns' => [
                [
                    'heading' => 'Explore',
                    'links' => [
                        ['label' => 'How we work', 'url' => $homeUrl . '#proof'],
                        ['label' => 'Field notes', 'url' => $homeUrl . '-directory'],
                    ],
                ],
                [
                    'heading' => 'Contact',
                    'links' => [
                        ['label' => 'Start a conversation', 'url' => 'mailto:' . self::SUPPORT_EMAIL],
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
