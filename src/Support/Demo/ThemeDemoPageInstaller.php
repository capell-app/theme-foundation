<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

use Capell\Core\Actions\CreateDefaultLanguagesAction;
use Capell\Core\Actions\CreateThemeAction;
use Capell\Core\Actions\SetupPageUrlsAction;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\Core\Support\Creator\PageCreator;
use Capell\FoundationTheme\Actions\BuildThemeDemoFormsPayloadAction;
use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static int run(ThemeDemoInstallData $data, string $themeKey, string $themeName, ?ProvidesThemeDemoContent $contentProvider = null)
 */
final class ThemeDemoPageInstaller
{
    use AsObject;

    public function handle(ThemeDemoInstallData $data, string $themeKey, string $themeName, ?ProvidesThemeDemoContent $contentProvider = null): int
    {
        $theme = $this->ensureTheme($themeKey, $themeName);
        $languages = $this->resolveLanguages($data);
        $sites = $this->resolveSites($data, $theme, $languages);
        $definitions = $contentProvider?->definitions($themeKey, $themeName, $data->baseUrl)
            ?? $this->definitions($themeKey, $themeName, $data->baseUrl);

        foreach ($sites as $site) {
            $this->installForSite($site, $languages, $definitions, $themeKey, $data->force);
            $this->dispatchFormBlueprints($site, $definitions);
        }

        return Command::SUCCESS;
    }

    private function ensureTheme(string $themeKey, string $themeName): Theme
    {
        return CreateThemeAction::run(key: $themeKey, name: $themeName);
    }

    /**
     * @return EloquentCollection<int, Language>
     */
    private function resolveLanguages(ThemeDemoInstallData $data): EloquentCollection
    {
        $languageCodes = $data->languageCodes;

        if ($languageCodes === []) {
            $defaultCode = Language::query()->default()->value('code');
            $languageCodes = [is_string($defaultCode) && $defaultCode !== '' ? $defaultCode : 'en'];
        }

        return CreateDefaultLanguagesAction::run($languageCodes);
    }

    /**
     * @param  EloquentCollection<int, Language>  $languages
     * @return EloquentCollection<int, Site>
     */
    private function resolveSites(ThemeDemoInstallData $data, Theme $theme, EloquentCollection $languages): EloquentCollection
    {
        $siteNames = $data->siteNames;

        if ($siteNames === []) {
            $defaultSiteName = Site::query()->default()->value('name');
            $siteNames = [is_string($defaultSiteName) && $defaultSiteName !== '' ? $defaultSiteName : 'Demo'];
        }

        $siteType = resolve(BlueprintCreator::class)->createSiteType();
        $sites = new EloquentCollection;

        foreach ($siteNames as $siteName) {
            $sites->push($this->ensureSite($siteName, $theme, $siteType, $languages, $data));
        }

        return $sites;
    }

    /**
     * @param  EloquentCollection<int, Language>  $languages
     */
    private function ensureSite(
        string $siteName,
        Theme $theme,
        Blueprint $siteType,
        EloquentCollection $languages,
        ThemeDemoInstallData $data,
    ): Site {
        $primaryLanguage = $languages->first();

        if (! $primaryLanguage instanceof Language) {
            $primaryLanguage = CreateDefaultLanguagesAction::run(['en'])->first();
        }

        /** @var Site $site */
        $site = Site::query()->firstOrNew(['name' => $siteName]);
        $site->fill([
            'blueprint_id' => $site->blueprint_id ?? $siteType->getKey(),
            'language_id' => $site->language_id ?? $primaryLanguage?->getKey(),
            'theme_id' => $theme->getKey(),
            'meta' => [
                'meta_schema' => [],
                ...($site->meta ?? []),
            ],
            'status' => true,
            'default' => $site->exists ? $site->default : ! Site::query()->default()->exists(),
        ]);
        $site->save();

        foreach ($languages as $languageIndex => $language) {
            $site->translations()->updateOrCreate(
                ['language_id' => $language->getKey()],
                [
                    'title' => $siteName,
                    'content' => '<p>' . e($siteName) . ' demo site.</p>',
                    'meta' => [
                        'title_after_text' => null,
                        'description' => sprintf('%s theme demo site for preview content.', $siteName),
                        'footer_copy' => sprintf('<p>%s demo content.</p>', $siteName),
                        'label' => null,
                        'ai_discovery' => [
                            'llms_txt_enabled' => false,
                            'llms_full_txt_enabled' => false,
                            'markdown_pages_enabled' => false,
                            'accept_markdown_enabled' => false,
                            'default_include_pages' => false,
                            'status' => null,
                            'default_section' => null,
                            'max_full_txt_pages' => null,
                            'max_full_txt_bytes' => null,
                            'cache_ttl_seconds' => null,
                            'intro_markdown' => null,
                        ],
                    ],
                ],
            );

            $site->siteDomains()->updateOrCreate(
                ['language_id' => $language->getKey()],
                [
                    ...$this->siteDomainData($data->baseUrl, $siteName, (string) $language->code, $languageIndex),
                    'default' => $languageIndex === 0,
                    'status' => true,
                ],
            );
        }

        return $site->refresh();
    }

    /**
     * @return array{scheme: string|null, domain: string|null, path: string|null}
     */
    private function siteDomainData(string $baseUrl, string $siteName, string $languageCode, int $languageIndex): array
    {
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $domain = parse_url($baseUrl, PHP_URL_HOST);
        $path = parse_url($baseUrl, PHP_URL_PATH);
        $pathParts = array_values(array_filter(explode('/', is_string($path) ? trim($path, '/') : '')));

        if ($languageIndex > 0) {
            $pathParts[] = $languageCode;
        }

        if (Site::query()->count() > 1) {
            $pathParts[] = Str::slug($siteName);
        }

        return [
            'scheme' => is_string($scheme) && $scheme !== '' ? $scheme : null,
            'domain' => is_string($domain) && $domain !== '' ? $domain : null,
            'path' => $pathParts === [] ? null : '/' . implode('/', $pathParts),
        ];
    }

    /**
     * @param  EloquentCollection<int, Language>  $languages
     * @param  array<int, ThemeDemoPageDefinition>  $definitions
     */
    private function installForSite(
        Site $site,
        EloquentCollection $languages,
        array $definitions,
        string $themeKey,
        bool $force,
    ): void {
        $creator = resolve(PageCreator::class);

        foreach ($definitions as $index => $definition) {
            $layout = $definition->hasContainers()
                ? $this->ensureDefinitionLayout($site, $themeKey, $definition)
                : null;

            $this->updateExistingPageLayout($site, $definition, $layout);

            $renderData = $this->renderDataForDefinition($definition);

            /** @var Page $page */
            $page = $creator->createPage([
                'name' => $definition->name,
                'type_key' => $definition->type,
                'layout_key' => $definition->layout,
                'layout_id' => $layout?->getKey(),
                'visible_from' => now()->subDay()->format('Y-m-d'),
                'meta' => [
                    'theme_demo' => [
                        'theme_key' => $themeKey,
                        'surface' => $definition->surface,
                        'render_data' => $renderData,
                    ],
                    'robots' => ['noindex' => $definition->surface === 'not-found'],
                ],
                'translations' => $this->translations($languages, $definition),
            ], $site, $languages);

            if ($definition->hasContainers()) {
                $this->installLayoutContainers($page, $definition);
            }

            if ($force || $page->order === null) {
                $page->forceFill(['order' => $index + 1])->save();
            }

            SetupPageUrlsAction::run($page);
        }
    }

    /**
     * Keep the optional form integration decoupled: Foundation emits a typed
     * scalar event payload and an installed form provider may consume it.
     *
     * @param  array<int, ThemeDemoPageDefinition>  $definitions
     */
    private function dispatchFormBlueprints(Site $site, array $definitions): void
    {
        $payload = BuildThemeDemoFormsPayloadAction::run($definitions);

        if ($payload === '[]') {
            return;
        }

        Event::dispatch('capell.theme-demo.forms', [$site->getKey(), $payload]);
    }

    /**
     * @param  array<string, mixed>  $renderData
     * @return array<string, mixed>
     */
    private function renderDataWithoutSections(array $renderData): array
    {
        unset($renderData['sections']);

        return $renderData;
    }

    /**
     * @return array<string, mixed>
     */
    private function renderDataForDefinition(ThemeDemoPageDefinition $definition): array
    {
        $renderData = $definition->hasContainers()
            ? $this->renderDataWithoutSections($definition->renderData)
            : $definition->renderData;

        if ($definition->surface === 'contact' && ! is_array(data_get($renderData, 'form'))) {
            $renderData['form'] = $this->contactFormData();
        }

        if ($definition->surface === 'contact' && ! $definition->hasContainers()) {
            $renderData = $this->renderDataWithContactSplit($renderData);
        }

        if ($definition->surface === 'empty') {
            $renderData = $this->renderDataWithSearchRecoverySection($renderData);
        }

        return $renderData;
    }

    /**
     * @param  array<string, mixed>  $renderData
     * @return array<string, mixed>
     */
    private function renderDataWithContactSplit(array $renderData): array
    {
        $sections = $renderData['sections'] ?? null;

        if (! is_array($sections)) {
            return $renderData;
        }

        foreach ($sections as $section) {
            if (is_array($section) && ($section['type'] ?? null) === 'contact-split') {
                return $renderData;
            }
        }

        $formSection = null;
        $remainingSections = [];

        foreach ($sections as $section) {
            if ($formSection === null && is_array($section) && ($section['type'] ?? null) === 'form') {
                $formSection = $section;

                continue;
            }

            $remainingSections[] = $section;
        }

        $contactForm = is_array($renderData['form'] ?? null) ? $renderData['form'] : $this->contactFormData();
        $contactSplit = [
            'type' => 'contact-split',
            'heading' => is_string(data_get($formSection, 'heading')) ? data_get($formSection, 'heading') : 'Start the right conversation',
            'summary' => is_string(data_get($formSection, 'summary')) ? data_get($formSection, 'summary') : (string) ($contactForm['summary'] ?? ''),
            'form_handle' => data_get($formSection, 'form_handle'),
            'form_instance_id' => data_get($formSection, 'form_instance_id', $contactForm['id'] ?? 'theme-demo-contact-form'),
            'fallback_message' => data_get($formSection, 'fallback_message'),
            'fallback_label' => data_get($formSection, 'fallback_label'),
            'fallback_url' => data_get($formSection, 'fallback_url'),
            'fields' => data_get($formSection, 'fields', $contactForm['fields'] ?? []),
            'address_lines' => ['Capell Studio', 'London, United Kingdom', 'Remote-first delivery'],
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ];

        array_unshift($remainingSections, $contactSplit);
        $renderData['sections'] = $remainingSections;

        return $renderData;
    }

    /**
     * @param  array<string, mixed>  $renderData
     * @return array<string, mixed>
     */
    private function renderDataWithSearchRecoverySection(array $renderData): array
    {
        $sections = $renderData['sections'] ?? null;

        if (! is_array($sections)) {
            return $renderData;
        }

        foreach ($sections as $section) {
            if (is_array($section) && ($section['type'] ?? null) === 'search') {
                return $renderData;
            }
        }

        $searchSection = [
            'type' => 'search',
            'heading' => 'Try another search',
            'summary' => 'Search again, clear the filter, or use the suggested paths below to continue browsing.',
            'action' => '/search',
            'query' => 'no matching results',
            'placeholder' => 'Search the archive',
            'results' => [],
        ];

        $insertAt = 1;

        foreach ($sections as $index => $section) {
            if (is_array($section) && ($section['type'] ?? null) === 'hero') {
                $insertAt = $index + 1;

                break;
            }
        }

        array_splice($sections, $insertAt, 0, [$searchSection]);
        $renderData['sections'] = $sections;

        return $renderData;
    }

    private function heroTitle(ThemeDemoPageDefinition $definition): string
    {
        $legacyHeroHeading = data_get($definition->renderData, 'hero.heading');

        if (is_string($legacyHeroHeading) && $legacyHeroHeading !== '') {
            return $legacyHeroHeading;
        }

        foreach ($definition->sections() as $section) {
            if (($section['type'] ?? null) !== 'hero') {
                continue;
            }

            $heading = $section['heading'] ?? null;

            if (is_string($heading) && $heading !== '') {
                return $heading;
            }
        }

        return $definition->title;
    }

    private function ensureDefinitionLayout(Site $site, string $themeKey, ThemeDemoPageDefinition $definition): Layout
    {
        $key = $this->definitionLayoutKey($site, $themeKey, $definition);
        $baseLayout = Layout::query()->firstWhere('key', $definition->layout->value);

        /** @var Layout $layout */
        $layout = Layout::query()->updateOrCreate(
            ['key' => $key],
            [
                'name' => sprintf('%s - %s', $definition->title, Str::headline($definition->surface)),
                'site_id' => $site->getKey(),
                'theme_id' => $site->theme_id,
                'group' => $baseLayout?->group ?? $definition->layout->value,
                'meta' => [
                    ...(is_array($baseLayout?->meta) ? $baseLayout->meta : []),
                    'theme_demo' => [
                        'theme_key' => $themeKey,
                        'surface' => $definition->surface,
                        'base_layout' => $definition->layout->value,
                    ],
                ],
                'admin' => is_array($baseLayout?->admin) ? $baseLayout->admin : [],
                'order' => $baseLayout?->order ?? 100,
                'default' => false,
                'status' => true,
            ],
        );

        return $layout;
    }

    private function definitionLayoutKey(Site $site, string $themeKey, ThemeDemoPageDefinition $definition): string
    {
        return Str::slug(sprintf(
            'theme-demo-%s-%s-%s-%s',
            $themeKey,
            $site->getKey(),
            $definition->layout->value,
            $definition->surface,
        ));
    }

    private function updateExistingPageLayout(Site $site, ThemeDemoPageDefinition $definition, ?Layout $layout = null): void
    {
        $layout ??= Layout::query()->firstWhere('key', $definition->layout->value);

        if (! $layout instanceof Layout) {
            return;
        }

        Page::query()
            ->where('site_id', $site->getKey())
            ->where('name', $definition->name)
            ->update(['layout_id' => $layout->getKey()]);
    }

    /**
     * Creates the widgets a definition's `containers` reference, then writes
     * those containers onto the resolved page-specific demo `Layout` model.
     *
     * A missing `Layout` model on `$page` is treated as a broken install
     * rather than a legitimate skip: `PageCreator::createPage()` always
     * resolves (and creates, if needed) a `layout_id`, so reaching this
     * method without a loaded `Layout` relation means something upstream
     * failed silently. By this point `render_data['sections']` has already
     * been stripped in favour of `$definition->containers`, so silently
     * returning here would leave the page half-seeded with no diagnostic.
     */
    private function installLayoutContainers(Page $page, ThemeDemoPageDefinition $definition): void
    {
        $layout = $page->layout;

        if (! $layout instanceof Layout) {
            throw new RuntimeException(sprintf(
                'Demo page [%s] has no resolvable layout; PageCreator::createPage() always resolves a layout_id (creating the Layout row if needed), so a missing Layout model here indicates a broken install rather than an expected skip.',
                $definition->name,
            ));
        }

        $this->createDefinitionWidgets($definition);

        $layout->update([
            'containers' => $definition->containers,
        ]);
    }

    private function createDefinitionWidgets(ThemeDemoPageDefinition $definition): void
    {
        $blueprints = $definition->widgets ?? [];

        if ($blueprints === []) {
            return;
        }

        $widgetCreator = resolve(WidgetCreator::class);

        foreach ($blueprints as $blueprint) {
            $method = $blueprint['method'] ?? null;

            if (! is_string($method) || $method === '' || ! method_exists($widgetCreator, $method)) {
                throw new InvalidArgumentException(sprintf(
                    'WidgetCreator has no method [%s] for demo widget blueprint.',
                    is_string($method) ? $method : get_debug_type($method),
                ));
            }

            $args = $blueprint['args'] ?? [];

            if (! is_array($args)) {
                throw new InvalidArgumentException(sprintf(
                    'Demo widget blueprint [%s] args must be an array.',
                    $method,
                ));
            }

            $widgetCreator->{$method}(...$args);
        }
    }

    /**
     * @param  EloquentCollection<int, Language>  $languages
     * @return array<string, array<string, mixed>>
     */
    private function translations(EloquentCollection $languages, ThemeDemoPageDefinition $definition): array
    {
        $translations = [];

        foreach ($languages as $language) {
            $translations[(string) $language->code] = [
                'title' => $definition->title,
                'content' => $this->contentWithoutDuplicateHeroHeading($definition),
                'summary' => $definition->renderData['summary'] ?? null,
                'meta' => [
                    'description' => $definition->renderData['summary'] ?? null,
                    'hero' => $definition->renderData['hero']['summary'] ?? null,
                    'hero_title' => $this->heroTitle($definition),
                    'label' => $definition->title,
                    'link_text' => $definition->renderData['link_text'] ?? 'View preview',
                    'slug' => $definition->slug,
                    'theme_demo' => $definition->renderData,
                ],
            ];
        }

        return $translations;
    }

    private function contentWithoutDuplicateHeroHeading(ThemeDemoPageDefinition $definition): string
    {
        $content = ltrim($definition->content);
        $heroTitle = $this->heroTitle($definition);

        if ($content === '' || $heroTitle === '') {
            return $definition->content;
        }

        $contentWithoutHeading = preg_replace_callback(
            '/^<h[1-3][^>]*>.*?<\\/h[1-3]>\\s*/is',
            fn (array $matches): string => $this->normaliseHeadingText($matches[0]) === $this->normaliseHeadingText($heroTitle)
                ? ''
                : $matches[0],
            $content,
            1,
        );

        return is_string($contentWithoutHeading) ? $contentWithoutHeading : $definition->content;
    }

    private function normaliseHeadingText(string $text): string
    {
        $plainText = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = preg_replace('/\\s+/u', ' ', $plainText);

        return Str::lower(trim(is_string($plainText) ? $plainText : ''));
    }

    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    private function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        $media = ThemeDemoMedia::groupedForTheme($themeKey);
        $profile = $this->profile($themeKey, $themeName);
        $brandName = $themeName . ' Demo';
        $basePath = rtrim($baseUrl, '/');

        $navigationItems = [
            ['label' => 'Home', 'url' => '#home'],
            ['label' => 'Directory', 'url' => '#directory'],
            ['label' => 'Contact', 'url' => '#contact'],
        ];
        $actions = [
            ['label' => 'View preview', 'url' => '#directory', 'style' => 'primary'],
            ['label' => 'Contact team', 'url' => '#contact', 'style' => 'secondary'],
        ];
        $footerColumns = [
            [
                'heading' => 'Preview',
                'links' => [
                    ['label' => 'Homepage', 'url' => '#home'],
                    ['label' => 'Directory', 'url' => '#directory'],
                ],
            ],
            [
                'heading' => 'Content',
                'links' => [
                    ['label' => 'Article', 'url' => '#article'],
                    ['label' => 'Empty state', 'url' => '#empty'],
                ],
            ],
            [
                'heading' => 'Support',
                'links' => [
                    ['label' => 'Contact', 'url' => '#contact'],
                    ['label' => '404', 'url' => '#not-found'],
                ],
            ],
        ];

        // Object-form chrome reused across every surface so the adapter's
        // navigationFrom()/footerFrom() take the brand + CTA + summary branch.
        // A bare-list form (or an absent key) collapses to
        // defaultNavigation()/defaultFooter() — brand + a lone "Home" link — which
        // is what left detail/contact/empty/404/cta rendering barren.
        $navigation = ['brandName' => $brandName, 'items' => $navigationItems, 'ctaLabel' => 'Contact', 'ctaUrl' => '#contact'];
        $footer = ['brandName' => $brandName, 'summary' => 'Footer links and copy for preview rendering.', 'columns' => $footerColumns];

        $definitions = [
            new ThemeDemoPageDefinition(
                surface: 'homepage',
                name: $brandName . ' Home',
                title: $brandName . ' Homepage',
                slug: 'theme-' . $themeKey,
                content: $this->content(
                    sprintf('%s homepage preview', $themeName),
                    'A portable homepage content sample with hero copy, proof points, navigation, CTA, footer data, and media references.',
                    $media['hero'][0],
                ),
                renderData: [
                    'summary' => $profile['summary'],
                    'navigation' => $navigation,
                    'hero' => [
                        'heading' => $profile['heroHeading'],
                        'eyebrow' => $themeName,
                        'summary' => $profile['heroSummary'],
                        'actions' => $actions,
                        'mediaUrl' => $media['hero'][0],
                        'mediaAlt' => sprintf('%s homepage media example', $themeName),
                    ],
                    'features_heading' => $profile['featuresHeading'],
                    'features_summary' => $profile['featuresSummary'],
                    'features' => $this->items($media['listing'], 'Homepage module', null, $profile['features']),
                    'spotlight' => [
                        'heading' => $profile['spotlightHeading'],
                        'summary' => $profile['spotlightSummary'],
                        'items' => $this->items($this->galleryMedia($media), 'Spotlight', null, $profile['spotlight']),
                        'variant' => 'spotlight',
                    ],
                    'gallery' => [
                        'heading' => $profile['galleryHeading'],
                        'summary' => $profile['gallerySummary'],
                        'items' => $this->items($this->galleryMedia($media), 'Gallery frame', null, $profile['gallery']),
                        'variant' => 'gallery',
                    ],
                    'items_heading' => $profile['pathwaysHeading'],
                    'items_summary' => $profile['pathwaysSummary'],
                    'items_variant' => 'pathways',
                    'items' => $this->items($this->galleryMedia($media), 'Pathway', null, $profile['pathways']),
                    'proof' => [
                        'heading' => $profile['proofHeading'],
                        'summary' => $profile['proofSummary'],
                        'items' => $this->proof($media['proof'], $profile['proof']),
                    ],
                    'cta' => ['heading' => $profile['ctaHeading'], 'summary' => $profile['ctaSummary'], 'actions' => $actions],
                    'footer' => $footer,
                    'image_urls' => ThemeDemoMedia::forTheme($themeKey),
                ],
                type: PageTypeEnum::Home,
                layout: LayoutEnum::Home,
            ),
            new ThemeDemoPageDefinition(
                surface: 'directory',
                name: $brandName . ' Directory',
                title: $brandName . ' Directory',
                slug: 'theme-' . $themeKey . '-directory',
                content: $this->content('Directory preview', 'A listing page sample for services, resources, products, or locations.', $media['listing'][0]),
                renderData: [
                    'summary' => 'Directory data includes cards, summaries, links, and image URLs.',
                    'heading' => 'Browse preview entries',
                    'items' => $this->items($media['listing'], 'Directory item', $basePath),
                    'navigation' => $navigation,
                    'footer' => $footer,
                ],
                layout: LayoutEnum::Results,
            ),
            new ThemeDemoPageDefinition(
                surface: 'detail',
                name: $brandName . ' Detail',
                title: $brandName . ' Detail Article',
                slug: 'theme-' . $themeKey . '-detail',
                content: $this->content('Article-style preview', 'A detail page sample with editorial copy, proof, and a referenced feature image.', $media['detail'][0]),
                renderData: [
                    'summary' => 'Detail page render data for an article, case study, product story, or service page.',
                    'navigation' => $navigation,
                    'hero' => ['heading' => 'Detail page story', 'summary' => 'Article-style page data for long-form previews.', 'mediaUrl' => $media['detail'][0]],
                    'related' => $this->items($media['listing'], 'Related item', $basePath),
                    'footer' => $footer,
                ],
            ),
            new ThemeDemoPageDefinition(
                surface: 'contact',
                name: $brandName . ' Contact',
                title: $brandName . ' Contact',
                slug: 'theme-' . $themeKey . '-contact',
                content: $this->contactContent(),
                renderData: [
                    'summary' => 'Contact page render data with routing cards, expectation details, and a static enquiry form.',
                    'navigation' => $navigation,
                    'hero' => ['heading' => 'Start the right conversation', 'summary' => 'Route project scoping, support, migrations, and partnerships to the right team.', 'mediaUrl' => $media['contact'][0]],
                    'actions' => [['label' => 'Send enquiry', 'url' => '#contact-form', 'style' => 'primary']],
                    'form' => $this->contactFormData(),
                    'footer' => $footer,
                ],
                layout: LayoutEnum::System,
            ),
            new ThemeDemoPageDefinition(
                surface: 'empty',
                name: $brandName . ' Empty State',
                title: $brandName . ' Empty State',
                slug: 'theme-' . $themeKey . '-empty',
                content: $this->content('Empty state preview', 'A graceful empty state for searches, filtered directories, catalogs, or resource hubs.', $media['cta'][0]),
                renderData: [
                    'summary' => 'Empty state data for no-results previews.',
                    'navigation' => $navigation,
                    'hero' => ['heading' => 'Nothing to show yet', 'summary' => 'Empty states still carry helpful copy and a next action.', 'mediaUrl' => $media['cta'][0]],
                    'actions' => $actions,
                    'items' => [],
                    'footer' => $footer,
                ],
            ),
            new ThemeDemoPageDefinition(
                surface: 'not-found',
                name: $brandName . ' 404',
                title: $brandName . ' Page Not Found',
                slug: 'theme-' . $themeKey . '-404',
                content: $this->content('404 preview', 'A not-found page sample with plain copy and a route back to useful content.', $media['cta'][0]),
                renderData: [
                    'summary' => '404 page data with recovery links and CTA copy.',
                    'navigation' => $navigation,
                    'hero' => ['heading' => 'Page not found', 'summary' => 'Help visitors recover with useful links and clear next steps.', 'mediaUrl' => $media['cta'][0]],
                    'actions' => [['label' => 'Return home', 'url' => '/', 'style' => 'primary']],
                    'footer' => $footer,
                ],
                type: PageTypeEnum::NotFound,
                layout: LayoutEnum::System,
            ),
            new ThemeDemoPageDefinition(
                surface: 'cta',
                name: $brandName . ' CTA',
                title: $brandName . ' CTA',
                slug: 'theme-' . $themeKey . '-cta',
                content: $this->content('CTA preview', 'A focused conversion page sample with semantic copy and action data.', $media['cta'][0]),
                renderData: [
                    'summary' => 'CTA page data for conversion-focused preview surfaces.',
                    'navigation' => $navigation,
                    'cta' => ['heading' => 'Ready for the next step?', 'summary' => 'This CTA is data-backed and presentation-agnostic.', 'actions' => $actions],
                    'mediaUrl' => $media['cta'][0],
                    'footer' => $footer,
                ],
            ),
        ];

        return [
            ...$definitions,
            ...$this->premiumDefinitions($themeKey, $themeName, $brandName, $baseUrl),
        ];
    }

    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    private function premiumDefinitions(string $themeKey, string $themeName, string $brandName, string $baseUrl): array
    {
        $surfaces = $this->premiumSurfaceProfiles()[$themeKey] ?? [];

        return array_map(
            fn (array $surface, int $index): ThemeDemoPageDefinition => $this->premiumDefinition($surface, $themeKey, $themeName, $brandName, $baseUrl, $index),
            $surfaces,
            array_keys($surfaces),
        );
    }

    /**
     * @param  array{surface: string, title: string, summary: string, sections: list<string>, items: list<array{title: string, summary: string, type?: string}>}  $surface
     */
    private function premiumDefinition(array $surface, string $themeKey, string $themeName, string $brandName, string $baseUrl, int $index): ThemeDemoPageDefinition
    {
        $media = ThemeDemoMedia::groupedForTheme($themeKey);
        $imageUrl = $media['listing'][$index % count($media['listing'])] ?? $media['hero'][0];
        $slug = 'theme-' . $themeKey . '-' . $surface['surface'];
        $actions = [
            ['label' => 'View details', 'url' => rtrim($baseUrl, '/') . '#' . $surface['surface'], 'style' => 'primary'],
            ['label' => 'Contact team', 'url' => '#contact', 'style' => 'secondary'],
        ];

        return new ThemeDemoPageDefinition(
            surface: $surface['surface'],
            name: $brandName . ' ' . $surface['title'],
            title: $brandName . ' ' . $surface['title'],
            slug: $slug,
            content: $this->content($surface['title'], $surface['summary'], $imageUrl),
            renderData: [
                'summary' => $surface['summary'],
                'navigation' => ['brandName' => $brandName, 'items' => $this->premiumNavigation($themeKey), 'ctaLabel' => 'Contact', 'ctaUrl' => '#contact'],
                'hero' => [
                    'heading' => $surface['title'],
                    'eyebrow' => $themeName,
                    'summary' => $surface['summary'],
                    'actions' => $actions,
                    'mediaUrl' => $imageUrl,
                    'mediaAlt' => sprintf('%s %s demo media', $themeName, $surface['title']),
                ],
                'sections' => $surface['sections'],
                'items_heading' => $surface['title'],
                'items_summary' => $surface['summary'],
                'items_variant' => 'gallery',
                'items' => $this->premiumItems($surface['items'], $media['listing'], rtrim($baseUrl, '/')),
                'features_heading' => $surface['title'] . ' modules',
                'features_summary' => 'Domain-specific render data for a premium theme page surface.',
                'features' => $surface['items'],
                'proof' => [
                    'heading' => $surface['title'] . ' proof',
                    'summary' => 'Static proof content stays portable and query-free for public rendering.',
                    'items' => $this->proof($media['proof'], $this->proofForSurface($surface['title'])),
                ],
                'cta' => ['heading' => 'Move forward with ' . $surface['title'], 'summary' => 'This page is seeded as portable Capell content with theme-owned presentation.', 'actions' => $actions],
                'footer' => ['brandName' => $brandName, 'summary' => $themeName . ' premium demo pages.', 'columns' => $this->premiumFooter($themeKey)],
            ],
            layout: $index === 0 ? LayoutEnum::Results : LayoutEnum::Default,
        );
    }

    /**
     * @param  list<array{title: string, summary: string, type?: string}>  $items
     * @param  list<string>  $imageUrls
     * @return list<array{title: string, summary: string, type?: string, url: string, image: string, imageUrl: string}>
     */
    private function premiumItems(array $items, array $imageUrls, string $baseUrl): array
    {
        return array_map(
            static fn (array $item, int $index): array => [
                'title' => $item['title'],
                'summary' => $item['summary'],
                'type' => $item['type'] ?? 'Page section',
                'url' => $baseUrl . '#premium-' . ($index + 1),
                'image' => $imageUrls[$index % count($imageUrls)] ?? '',
                'imageUrl' => $imageUrls[$index % count($imageUrls)] ?? '',
            ],
            $items,
            array_keys($items),
        );
    }

    /**
     * @return list<array{metric: string, name: string, quote: string}>
     */
    private function proofForSurface(string $title): array
    {
        return [
            ['metric' => '3+', 'name' => 'Premium page surfaces', 'quote' => $title . ' includes enough structure to preview more than one homepage.'],
            ['metric' => '0', 'name' => 'Public queries', 'quote' => 'Demo render data is hydrated before Blade and stays safe for cached output.'],
            ['metric' => '7+', 'name' => 'Reusable sections', 'quote' => 'Editors can combine standard and vertical sections without storing designed markup.'],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function premiumNavigation(string $themeKey): array
    {
        return [
            ['label' => 'Home', 'url' => '#home'],
            ['label' => 'Pages', 'url' => '#pages'],
            ['label' => Str::headline($themeKey), 'url' => '#theme'],
            ['label' => 'Contact', 'url' => '#contact'],
        ];
    }

    /**
     * @return list<array{heading: string, links: list<array{label: string, url: string}>}>
     */
    private function premiumFooter(string $themeKey): array
    {
        return [
            ['heading' => 'Theme pages', 'links' => [['label' => 'Homepage', 'url' => '#home'], ['label' => 'Directory', 'url' => '#directory']]],
            ['heading' => Str::headline($themeKey), 'links' => [['label' => 'Landing', 'url' => '#landing'], ['label' => 'Detail', 'url' => '#detail']]],
            ['heading' => 'Conversion', 'links' => [['label' => 'Contact', 'url' => '#contact'], ['label' => 'CTA', 'url' => '#cta']]],
        ];
    }

    /**
     * @return array<string, list<array{surface: string, title: string, summary: string, sections: list<string>, items: list<array{title: string, summary: string, type?: string}>}>>
     */
    private function premiumSurfaceProfiles(): array
    {
        return [
            'ai-lab' => $this->premiumSurfaces(['model-suite', 'Model Suite', 'Compare frontier, mini, code, and embedding models with context windows, modalities, and availability.', ['model-cards', 'benchmarks', 'playground']], ['research-library', 'Research Library', 'Publish papers, evaluations, changelog notes, and reproducible benchmark context.', ['research-index', 'content-listing', 'proof']], ['playground-preview', 'Playground Preview', 'Show a static prompt and response journey without making public API calls.', ['playground', 'features', 'cta']]),
            'api-platform' => $this->premiumSurfaces(['quickstart', 'Quickstart', 'Help developers move from key creation to the first successful API call.', ['code-hero', 'quickstart', 'sdk-grid']], ['api-reference', 'API Reference', 'Document endpoints, SDKs, uptime, and implementation routes for technical buyers.', ['api-reference-teaser', 'status-uptime', 'content-listing']], ['status', 'Status & Trust', 'Show uptime, changelog, support guarantees, and production readiness.', ['status-uptime', 'proof', 'cta']]),
            'ai-agent' => $this->premiumSurfaces(['use-cases', 'Use Cases', 'Route support, operations, and manufacturing workflows into agent-ready journeys.', ['use-cases', 'agent-in-action', 'outcome-metrics']], ['integrations', 'Integrations', 'Show the systems the agent reads, writes, and resolves across.', ['integrations-grid', 'features', 'proof']], ['roi', 'ROI Calculator', 'Present static savings, queue reduction, and handoff assumptions for evaluation.', ['roi-calculator', 'proof', 'cta']]),
            'aeo-analytics' => $this->premiumSurfaces(['dashboard', 'Dashboard', 'Preview visibility scorecards, answer-engine coverage, and narrative monitoring.', ['dashboard-preview', 'metric-cards', 'coverage-map']], ['reports', 'Reports', 'Package board-ready reports, recommendation logs, and competitor tracking.', ['report-gallery', 'content-listing', 'proof']], ['integrations', 'Integrations', 'Show supported answer engines, analytics tools, and export workflows.', ['integrations-grid', 'features', 'cta']]),
            'fintech-trust' => $this->premiumSurfaces(['verification-flow', 'Verification Flow', 'Explain business identity checks, ownership paths, sanctions screening, and review states.', ['verification-flow', 'compliance-badges', 'metric-cards']], ['security', 'Security Architecture', 'Show audit trails, compliance posture, and operational controls for regulated buyers.', ['security-architecture', 'coverage-map', 'proof']], ['compliance', 'Compliance Hub', 'Collect policy, audit, risk, and support content into a high-trust resource surface.', ['compliance-badges', 'content-listing', 'cta']]),
            'crypto-defi' => $this->premiumSurfaces(['markets', 'Markets', 'Show protocol stats, collateral assets, APY examples, and liquidity context.', ['protocol-stats', 'token-metrics', 'wallet-cta']], ['how-it-works', 'How It Works', 'Explain lending, borrowing, risk, and settlement in a public-safe flow.', ['how-it-works', 'audit-badges', 'proof']], ['audit', 'Audit & Risk', 'Surface audits, disclaimers, risk controls, and governance links.', ['audit-badges', 'content-listing', 'cta']]),
            'quant-trading' => $this->premiumSurfaces(['performance', 'Performance', 'Present static equity curves, drawdown context, and strategy summaries with risk disclosure.', ['performance-chart', 'track-record-table', 'risk-disclosure']], ['strategies', 'Strategies', 'Group systematic strategy cards, markets, and research notes.', ['strategy-cards', 'metric-cards', 'content-listing']], ['risk', 'Risk Disclosure', 'Make risk language, methodology, and investor qualification visible.', ['risk-disclosure', 'proof', 'cta']]),
            'devtool-oss' => $this->premiumSurfaces(['install', 'Install', 'Lead with install commands, self-host options, and SDK entry points.', ['install-hero', 'quickstart', 'sdk-grid']], ['community', 'Community', 'Show GitHub proof, contributors, changelog, and governance routes.', ['github-proof', 'contributors', 'changelog']], ['cloud', 'Cloud vs Self-host', 'Compare hosted and self-hosted paths for evaluation.', ['self-host-vs-cloud', 'features', 'cta']]),
            'robotics-hardware' => $this->premiumSurfaces(['product', 'Product', 'Show a hardware product story with capability cards, preorder CTA, and video-ready media.', ['video-hero', 'capabilities', 'preorder-cta']], ['specs', 'Specs', 'Publish dimensions, reach, battery, safety, and support details.', ['spec-sheet', 'tech-deep-dive', 'proof']], ['preorder', 'Preorder', 'Guide buyers from product confidence into reservation or demo interest.', ['preorder-cta', 'features', 'cta']]),
            'manufacturing' => $this->premiumSurfaces(['capabilities', 'Capabilities', 'Show machining, fabrication, QA, and fulfilment capabilities.', ['capabilities-grid', 'facility-stats', 'certifications']], ['case-studies', 'Case Studies', 'Present production examples, tolerances, lead times, and results.', ['case-studies', 'spec-downloads', 'proof']], ['rfq', 'RFQ', 'Create a conversion path for drawings, materials, volumes, and timelines.', ['rfq-form', 'features', 'cta']]),
            'packaging-supplier' => $this->premiumSurfaces(['products', 'Product Range', 'Group cartons, mailers, labels, inserts, and custom packaging choices.', ['product-range', 'materials', 'industries']], ['sustainability', 'Sustainability', 'Show materials, certifications, lifecycle claims, and responsible sourcing.', ['sustainability', 'proof', 'content-listing']], ['samples', 'Sample Request', 'Route buyers into sample kits, MOQ questions, and quote requests.', ['sample-request', 'features', 'cta']]),
            'conference-event' => $this->premiumSurfaces(['agenda', 'Agenda', 'Show schedule blocks, session tracks, and conference flow.', ['event-hero', 'agenda', 'speakers']], ['tickets', 'Tickets', 'Present ticket tiers, venue details, sponsors, and conversion actions.', ['ticket-tiers', 'venue', 'sponsors']], ['speakers', 'Speakers', 'Feature speakers, sessions, sponsor proof, and content recaps.', ['speakers', 'content-listing', 'proof']]),
            'podcast-show' => $this->premiumSurfaces(['episodes', 'Episodes', 'List latest episodes, guests, hosts, and listening routes.', ['latest-episode', 'episode-list', 'subscribe-platforms']], ['guests', 'Guests', 'Show guest profiles, topics, and featured conversations.', ['guests', 'hosts', 'proof']], ['sponsors', 'Sponsors', 'Give sponsors placements, packages, and audience proof.', ['sponsors', 'features', 'cta']]),
            'newsroom-magazine' => $this->premiumSurfaces(['front-page', 'Front Page', 'Lead with featured stories, category nav, most-read content, and newsletter signup.', ['featured-story', 'category-nav', 'story-grid']], ['contributors', 'Contributors', 'Show writers, editors, beats, and editorial credibility.', ['contributors', 'most-read', 'proof']], ['newsletter', 'Newsletter', 'Convert readers through newsletter value, archive proof, and story pathways.', ['newsletter-signup', 'content-listing', 'cta']]),
            'design-studio' => $this->premiumSurfaces(['projects', 'Projects', 'Present interiors, hospitality spaces, and project outcomes with a gallery rhythm.', ['project-gallery', 'services', 'awards']], ['studio', 'Studio', 'Explain design philosophy, services, awards, and consultation flow.', ['services', 'awards', 'proof']], ['case-study', 'Case Study', 'Show one project through brief, constraints, material choices, and result.', ['project-gallery', 'content-listing', 'cta']]),
            'product-studio' => $this->premiumSurfaces(['case-studies', 'Case Studies', 'Show shipped products, measurable outcomes, and engagement context.', ['case-studies', 'tech-stack', 'process']], ['engagements', 'Engagement Models', 'Compare sprint, retainer, and build-partner working models.', ['engagement-models', 'process', 'proof']], ['stack', 'Tech Stack', 'Present tools, frameworks, delivery principles, and maintenance support.', ['tech-stack', 'features', 'cta']]),
            'personal-dev' => $this->premiumSurfaces(['writing', 'Writing', 'Show essays, notes, project updates, and newsletter entry points.', ['writing-index', 'newsletter-inline', 'now']], ['projects', 'Projects', 'Present selected work, open-source projects, and case notes.', ['projects', 'about-intro', 'proof']], ['now', 'Now Page', 'Give a personal site a current-status page with focus, availability, and links.', ['now', 'features', 'cta']]),
            'creator-newsletter' => $this->premiumSurfaces(['archive', 'Archive', 'Show issue archives, subscription value, testimonials, and sponsor slots.', ['subscribe-hero', 'archive', 'testimonials']], ['author', 'Author', 'Build trust with author background, sponsors, and reader proof.', ['about-author', 'sponsors', 'proof']], ['subscribe', 'Subscribe', 'Create a dedicated conversion page for the newsletter offer.', ['subscribe-hero', 'features', 'cta']]),
            'law-firm' => $this->premiumSurfaces(['practice-areas', 'Practice Areas', 'Show legal services, credentials, attorneys, and consultation routes.', ['practice-areas', 'credentials', 'attorneys']], ['attorneys', 'Attorneys', 'Present partner profiles, expertise, admissions, and case context.', ['attorneys', 'case-results', 'proof']], ['consultation', 'Consultation', 'Route prospects into intake topics and next-step expectations.', ['consultation-cta', 'features', 'cta']]),
            'financial-advisory' => $this->premiumSurfaces(['services', 'Services', 'Group advisory, tax, audit, and planning services for high-trust browsing.', ['services', 'credentials', 'client-segments']], ['advisors', 'Advisors', 'Feature advisory team profiles, credentials, and specialisms.', ['advisors', 'proof', 'content-listing']], ['calculators', 'Calculators', 'Show static planning calculators, assumptions, and consultation CTAs.', ['calculators', 'features', 'cta']]),
            'construction-trades' => $this->premiumSurfaces(['services', 'Services', 'Show renovation, extension, repair, and trade service routes.', ['services', 'service-areas', 'accreditations']], ['projects', 'Projects', 'Present project portfolio, process, proof, and before-after style evidence.', ['project-portfolio', 'process', 'proof']], ['quote', 'Quote Request', 'Guide visitors into a quote path with scope, area, and timing expectations.', ['quote-cta', 'features', 'cta']]),
            'fitness-wellness' => $this->premiumSurfaces(['classes', 'Classes', 'Show class types, training blocks, membership paths, and schedule expectations.', ['class-schedule', 'coach-profiles', 'challenge-board']], ['coaches', 'Coaches', 'Feature coaches, specialties, proof, and onboarding routes.', ['coach-profiles', 'nutrition-guides', 'proof']], ['membership', 'Membership', 'Present membership options, conversion copy, and studio proof.', ['membership-plans', 'features', 'cta']]),
            'beauty-spa' => $this->premiumSurfaces(['treatments', 'Treatments', 'Show treatment menus, packages, durations, and suitability notes.', ['treatment-menu', 'therapist-profiles', 'before-after-proof']], ['packages', 'Packages', 'Present spa packages, seasonal offers, and booking routes.', ['package-grid', 'treatment-menu', 'proof']], ['booking', 'Booking', 'Give visitors a calm conversion page for appointment requests.', ['booking-panel', 'features', 'cta']]),
            'travel-tourism' => $this->premiumSurfaces(['destinations', 'Destinations', 'Show destination cards, itinerary styles, and trip proof.', ['destination-grid', 'guide-profiles', 'trip-inclusions']], ['itineraries', 'Itineraries', 'Present day-by-day trip structures, inclusions, and enquiry routes.', ['itinerary-builder', 'destination-grid', 'proof']], ['enquiry', 'Travel Enquiry', 'Guide travellers into a quote or planning conversation.', ['enquiry-panel', 'features', 'cta']]),
            'automotive-dealer' => $this->premiumSurfaces(['inventory', 'Inventory', 'Show vehicle listings, specs, finance cues, and enquiry actions.', ['inventory-grid', 'finance-options', 'test-drive-panel']], ['vehicle-detail', 'Vehicle Detail', 'Present a vehicle story with highlights, condition, specs, and CTA.', ['vehicle-detail', 'part-exchange', 'proof']], ['finance', 'Finance', 'Explain finance options, part exchange, and buying confidence.', ['finance-options', 'features', 'cta']]),
            'property-developer' => $this->premiumSurfaces(['developments', 'Developments', 'Show development cards, availability, local proof, and launch status.', ['development-grid', 'availability-table', 'location-guide']], ['floorplans', 'Floorplans', 'Present home types, layouts, specifications, and availability CTAs.', ['floorplans', 'development-grid', 'proof']], ['location', 'Location', 'Sell the neighbourhood, travel links, amenities, and lifestyle proof.', ['location-guide', 'viewing-panel', 'cta']]),
            'recruitment-jobs' => $this->premiumSurfaces(['jobs', 'Jobs', 'Show roles, sectors, filters, salary context, and apply CTAs.', ['job-board', 'sector-specialisms', 'application-panel']], ['employers', 'Employers', 'Present hiring services, process, proof, and consultation routes.', ['employer-services', 'sector-specialisms', 'proof']], ['candidate-advice', 'Candidate Advice', 'Publish advice, interview guides, and sector insights.', ['candidate-advice', 'content-listing', 'cta']]),
            'blog' => $this->premiumSurfaces(['essays', 'Essays', 'Show long-form editorial pages, issue framing, and reading pathways.', ['essay-index', 'author-profiles', 'editorial-statement']], ['archive', 'Archive', 'Present an archive, categories, series, and contributor proof.', ['issue-archive', 'essay-index', 'proof']], ['about', 'About the Publication', 'Introduce editorial principles, contributors, and subscription actions.', ['editorial-statement', 'subscription-panel', 'cta']]),
        ];
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: list<string>}  $first
     * @param  array{0: string, 1: string, 2: string, 3: list<string>}  $second
     * @param  array{0: string, 1: string, 2: string, 3: list<string>}  $third
     * @return list<array{surface: string, title: string, summary: string, sections: list<string>, items: list<array{title: string, summary: string, type?: string}>}>
     */
    private function premiumSurfaces(array $first, array $second, array $third): array
    {
        return array_map(
            static fn (array $surface): array => [
                'surface' => $surface[0],
                'title' => $surface[1],
                'summary' => $surface[2],
                'sections' => $surface[3],
                'items' => array_values(array_map(
                    static fn (string $section): array => [
                        'title' => Str::headline($section),
                        'summary' => sprintf('A %s section tailored for this premium page surface.', Str::headline($section)),
                        'type' => 'Section',
                    ],
                    $surface[3],
                )),
            ],
            [$first, $second, $third],
        );
    }

    private function content(string $heading, string $summary, string $imageUrl): string
    {
        return sprintf(
            '<h2>%s</h2><p>%s</p><p>Example media URL: <a href="%s">%s</a></p>',
            e($heading),
            e($summary),
            e($imageUrl),
            e($imageUrl),
        );
    }

    private function contactContent(): string
    {
        return <<<'HTML'
<h2>Start the right conversation</h2>
<p>Tell us what you are planning, fixing, moving, or partnering on. One contact page routes project scoping, technical support, migrations, and partnerships to the right Capell team.</p>
<h3>Capell Studio, London</h3>
<p>Remote-first delivery with UK timezone handover. Send an enquiry and the contact form routes it into the right follow-up path.</p>
<h3>Project scoping</h3>
<p>Plan content models, package boundaries, layouts, and launch checks before the build starts.</p>
<h3>Support</h3>
<p>Route production issues, editor workflow questions, and package troubleshooting to the right owner.</p>
<h3>Migration planning</h3>
<p>Map pages, redirects, media, structured fields, and verification work into a clear migration path.</p>
<h3>Partnerships</h3>
<p>Discuss delivery partnerships, packaged integrations, and repeatable theme or content operations.</p>
<ul>
    <li><strong>Response:</strong> Within 4 business hours</li>
    <li><strong>Location:</strong> London, UK and remote-first</li>
    <li><strong>Handover:</strong> Directly routed to the right team</li>
</ul>
HTML;
    }

    /**
     * @return array{
     *     id: string,
     *     heading: string,
     *     summary: string,
     *     topics: array<int, string>,
     *     fields: array<int, array{name: string, label: string, type: string}>
     * }
     */
    private function contactFormData(): array
    {
        return [
            'id' => 'theme-demo-contact-form',
            'heading' => 'Send an enquiry',
            'summary' => 'Share the context once so the right delivery, support, migration, or partnership lead can follow up.',
            'topics' => [
                'Project scoping',
                'Support',
                'Migration planning',
                'Partnerships',
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Work email', 'type' => 'email'],
                ['name' => 'company', 'label' => 'Company', 'type' => 'text'],
                ['name' => 'message', 'label' => 'Message', 'type' => 'textarea'],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $imageUrls
     * @param  array<int, array{title: string, summary: string, type?: string}>  $copy
     * @return array<int, array<string, string>>
     */
    private function items(array $imageUrls, string $label, ?string $baseUrl = null, array $copy = []): array
    {
        return array_map(
            static fn (string $imageUrl, int $index): array => [
                'title' => $copy[$index]['title'] ?? sprintf('%s %d', $label, $index + 1),
                'summary' => $copy[$index]['summary'] ?? 'Preview item copy that can render as a card, row, or teaser.',
                'url' => ($baseUrl ?? '') . '#item-' . ($index + 1),
                'image' => $imageUrl,
                'imageUrl' => $imageUrl,
                'type' => $copy[$index]['type'] ?? 'Preview',
            ],
            $imageUrls,
            array_keys($imageUrls),
        );
    }

    /**
     * @param  array{hero: array<int, string>, listing: array<int, string>, detail: array<int, string>, proof: array<int, string>, contact: array<int, string>, cta: array<int, string>}  $media
     * @return array<int, string>
     */
    private function galleryMedia(array $media): array
    {
        return array_slice(array_values(array_unique(array_merge(
            $media['listing'],
            $media['detail'],
            $media['proof'],
            $media['cta'],
        ))), 0, 4);
    }

    /**
     * @return array{
     *     summary: string,
     *     heroHeading: string,
     *     heroSummary: string,
     *     featuresHeading: string,
     *     featuresSummary: string,
     *     features: array<int, array{title: string, summary: string, type?: string}>,
     *     spotlightHeading: string,
     *     spotlightSummary: string,
     *     spotlight: array<int, array{title: string, summary: string, type?: string}>,
     *     galleryHeading: string,
     *     gallerySummary: string,
     *     gallery: array<int, array{title: string, summary: string, type?: string}>,
     *     pathwaysHeading: string,
     *     pathwaysSummary: string,
     *     pathways: array<int, array{title: string, summary: string, type?: string}>,
     *     proofHeading: string,
     *     proofSummary: string,
     *     proof: array<int, array{metric: string, name: string, quote: string}>,
     *     ctaHeading: string,
     *     ctaSummary: string
     * }
     */
    private function profile(string $themeKey, string $themeName): array
    {
        $defaultProfile = [
            'summary' => sprintf('Homepage preview content for the %s theme.', $themeName),
            'heroHeading' => sprintf('Launch a polished %s site', Str::lower($themeName)),
            'heroSummary' => 'A complete first screen with semantic copy and remote media ready for preview rendering.',
            'featuresHeading' => 'Featured modules',
            'featuresSummary' => sprintf('Homepage preview content for the %s theme.', $themeName),
            'features' => [],
            'spotlightHeading' => sprintf('%s buying moments', $themeName),
            'spotlightSummary' => "Tabbed spotlight panels let visitors compare the theme's strongest content moments without leaving the page.",
            'spotlight' => $this->defaultSpotlightItems($themeName),
            'galleryHeading' => sprintf('%s layout gallery', $themeName),
            'gallerySummary' => 'A carousel-ready media section for campaigns, featured work, resources, and proof surfaces.',
            'gallery' => $this->defaultGalleryItems($themeName),
            'pathwaysHeading' => sprintf('Launch paths for %s sites', Str::lower($themeName)),
            'pathwaysSummary' => 'Accordion-style pathways give buyers and editors clear ways to imagine the theme beyond one homepage.',
            'pathways' => $this->defaultPathwayItems($themeName),
            'proofHeading' => 'Proof that the theme can carry real pages',
            'proofSummary' => 'Evidence widgets pair outcomes with media so previews feel closer to launchable sites.',
            'proof' => $this->defaultProofItems($themeName),
            'ctaHeading' => 'Turn this preview into a real site',
            'ctaSummary' => 'CTA copy is stored as data, not presentation markup.',
        ];

        /** @var array<string, array{summary: string, heroHeading: string, heroSummary: string, featuresHeading: string, featuresSummary: string, features: list<array{title: string, summary: string, type: string}>, ctaHeading: string, ctaSummary: string}> $profiles */
        $profiles = [
            'agency' => [
                'summary' => 'A high-contrast agency homepage with campaign proof, project cards, and decisive conversion paths.',
                'heroHeading' => 'Win sharper briefs with a bolder agency site',
                'heroSummary' => 'Lead with portfolio-grade media, crisp positioning, and reusable proof widgets that can survive real client edits.',
                'featuresHeading' => 'Built for teams selling creative judgment',
                'featuresSummary' => 'The page gives agencies enough visual rhythm for brand work without trapping content inside a one-off template.',
                'features' => [
                    ['title' => 'Campaign-grade hero', 'summary' => 'Oversized type, confident contrast, and media framing make the first screen feel intentional.', 'type' => 'Hero'],
                    ['title' => 'Proof-led modules', 'summary' => 'Reusable proof cards let studios show outcomes, quotes, and momentum without custom markup.', 'type' => 'Proof'],
                    ['title' => 'Case-study pathways', 'summary' => 'Directory and detail surfaces route visitors into services, work, and contact naturally.', 'type' => 'Work'],
                ],
                'ctaHeading' => 'Package the next agency launch',
                'ctaSummary' => 'Use this theme when the public site needs to feel confident before a single custom component is written.',
            ],
            'corporate' => [
                'summary' => 'A composed corporate homepage for service lines, trust signals, reports, and stakeholder journeys.',
                'heroHeading' => 'Present a serious organisation with less ceremony',
                'heroSummary' => 'Structured sections keep corporate content credible, scan-friendly, and easy to govern across departments.',
                'featuresHeading' => 'Designed for clarity under review',
                'featuresSummary' => 'The theme balances restrained visuals with enough polish for leadership, investor, and service pages.',
                'features' => [
                    ['title' => 'Executive first screen', 'summary' => 'Calm hierarchy and architectural media establish scale without feeling like a generic brochure.', 'type' => 'Positioning'],
                    ['title' => 'Governed content cards', 'summary' => 'Service, report, and resource cards stay consistent when multiple teams publish.', 'type' => 'Governance'],
                    ['title' => 'Trust pathways', 'summary' => 'Proof, directory, and CTA sections help visitors move from evaluation to enquiry.', 'type' => 'Trust'],
                ],
                'ctaHeading' => 'Ship a corporate surface that holds up',
                'ctaSummary' => 'Start from a theme that looks credible in reviews and remains maintainable after launch.',
            ],
            'commerce' => [
                'summary' => 'An editorial commerce homepage for collections, product stories, proof, and campaign merchandising.',
                'heroHeading' => 'Make commerce feel curated, not catalogued',
                'heroSummary' => 'Blend editorial storytelling with product pathways so collections, offers, and content support each other.',
                'featuresHeading' => 'A richer storefront rhythm',
                'featuresSummary' => 'Commerce pages need more than product grids; this theme gives campaigns room to breathe.',
                'features' => [
                    ['title' => 'Collection storytelling', 'summary' => 'Hero and card sections frame products around season, value, and use case.', 'type' => 'Merchandising'],
                    ['title' => 'Product proof', 'summary' => 'Proof modules support reviews, guarantees, stock cues, and buyer confidence.', 'type' => 'Conversion'],
                    ['title' => 'Content-led browsing', 'summary' => 'Directory and detail pages connect categories, guides, and featured products.', 'type' => 'Discovery'],
                ],
                'ctaHeading' => 'Turn browsing into a stronger buying path',
                'ctaSummary' => 'Use editorial commerce when a store needs premium context around the catalogue.',
            ],
            'education' => [
                'summary' => 'A course-led homepage for programmes, instructors, cohorts, open days, and enrolment journeys.',
                'heroHeading' => 'Turn programmes into a confident enrolment journey',
                'heroSummary' => 'Show learning outcomes, cohort energy, and course pathways with content editors can update every term.',
                'featuresHeading' => 'Everything learners need before they apply',
                'featuresSummary' => 'The theme makes courses, instructors, resources, and enrolment actions feel connected.',
                'features' => [
                    ['title' => 'Course discovery', 'summary' => 'Programme cards can highlight level, format, start dates, and outcomes.', 'type' => 'Courses'],
                    ['title' => 'Instructor credibility', 'summary' => 'People-led sections give teaching teams and subject experts proper space.', 'type' => 'Faculty'],
                    ['title' => 'Enrolment prompts', 'summary' => 'CTA and form-ready sections keep applications, open days, and enquiries close.', 'type' => 'Conversion'],
                ],
                'ctaHeading' => 'Open the next cohort with a better first impression',
                'ctaSummary' => 'Use the education theme when course content needs structure and a premium public face.',
            ],
            'healthcare' => [
                'summary' => 'A healthcare homepage for services, clinicians, appointment paths, local proof, and patient reassurance.',
                'heroHeading' => 'Help patients choose the right care faster',
                'heroSummary' => 'Create calm, trustworthy healthcare pages with clear service paths and appointment-focused actions.',
                'featuresHeading' => 'Patient-centred sections',
                'featuresSummary' => 'The theme gives clinical teams enough structure for services, proof, people, and booking.',
                'features' => [
                    ['title' => 'Service routing', 'summary' => 'Guide patients from symptoms or service areas into the right next step.', 'type' => 'Services'],
                    ['title' => 'Clinician trust', 'summary' => 'Feature care teams, accreditations, and reassurance without clutter.', 'type' => 'Trust'],
                    ['title' => 'Booking-ready CTAs', 'summary' => 'Keep enquiry and appointment paths visible across the public journey.', 'type' => 'Access'],
                ],
                'ctaHeading' => 'Make the care pathway easier to act on',
                'ctaSummary' => 'Use healthcare when public pages need warmth, clarity, and operational discipline.',
            ],
            'knowledge' => [
                'summary' => 'A knowledge-base homepage for topic hubs, featured resources, search, authors, and editorial depth.',
                'heroHeading' => 'Make expertise easier to browse and trust',
                'heroSummary' => 'Present guides, research, resources, and authors as a coherent content product instead of a loose archive.',
                'featuresHeading' => 'A home for serious content libraries',
                'featuresSummary' => 'Knowledge pages need search, structure, and editorial signals that reward repeat visitors.',
                'features' => [
                    ['title' => 'Topic pathways', 'summary' => 'Hub sections group resources around intent, stage, or audience.', 'type' => 'Taxonomy'],
                    ['title' => 'Featured thinking', 'summary' => 'Editorial cards make important guides and reports feel current.', 'type' => 'Editorial'],
                    ['title' => 'Author trust', 'summary' => 'People and proof modules reinforce why the content is worth reading.', 'type' => 'Authority'],
                ],
                'ctaHeading' => 'Turn the resource library into a product',
                'ctaSummary' => 'Use the knowledge theme when content is a reason to return, not a support appendix.',
            ],
            'local-services' => [
                'summary' => 'A quote-led local services homepage for service areas, reviews, cases, contact, and fast enquiry.',
                'heroHeading' => 'Convert local intent into booked work',
                'heroSummary' => 'Make services, coverage areas, proof, and quote requests obvious for visitors who need help now.',
                'featuresHeading' => 'Built for high-intent local journeys',
                'featuresSummary' => 'The theme keeps credibility, geography, and contact paths visible without feeling like a template.',
                'features' => [
                    ['title' => 'Service-area clarity', 'summary' => 'Show where the team works and which jobs are a good fit.', 'type' => 'Local SEO'],
                    ['title' => 'Quote-first flow', 'summary' => 'CTA and contact sections support fast enquiries without burying details.', 'type' => 'Lead gen'],
                    ['title' => 'Case proof', 'summary' => 'Before-and-after style cards and reviews make the business feel real.', 'type' => 'Proof'],
                ],
                'ctaHeading' => 'Make the next quote request easier',
                'ctaSummary' => 'Use local services when a business needs trust, coverage, and conversion in the same screen.',
            ],
            'nonprofit' => [
                'summary' => 'An impact-led nonprofit homepage for campaigns, giving, volunteering, events, and community stories.',
                'heroHeading' => 'Show the impact before asking for support',
                'heroSummary' => 'Lead with mission, outcomes, and ways to help so supporters can understand and act quickly.',
                'featuresHeading' => 'Campaign-ready civic pages',
                'featuresSummary' => 'Nonprofit content needs emotion, proof, and practical next steps in equal measure.',
                'features' => [
                    ['title' => 'Impact proof', 'summary' => 'Metrics and story cards connect donations and volunteering to real outcomes.', 'type' => 'Impact'],
                    ['title' => 'Campaign paths', 'summary' => 'Give each campaign, event, and appeal a structured route from awareness to action.', 'type' => 'Campaigns'],
                    ['title' => 'Supporter actions', 'summary' => 'Donation, volunteer, newsletter, and contact CTAs can sit together without confusion.', 'type' => 'Action'],
                ],
                'ctaHeading' => 'Make support feel immediate and useful',
                'ctaSummary' => 'Use nonprofit when public pages need to move people from belief to action.',
            ],
            'portfolio' => [
                'summary' => 'A portfolio homepage for selected work, case studies, services, testimonials, media kits, and newsletters.',
                'heroHeading' => 'Make the work feel selective and worth hiring',
                'heroSummary' => 'Give creators, consultants, and studios a premium public surface for proof, perspective, and enquiries.',
                'featuresHeading' => 'Portfolio structure beyond a grid',
                'featuresSummary' => 'The theme gives work, services, speaking, and newsletter surfaces a consistent editorial frame.',
                'features' => [
                    ['title' => 'Selected work', 'summary' => 'Feature strong projects without making every page a custom case study.', 'type' => 'Work'],
                    ['title' => 'Service framing', 'summary' => 'Explain what someone can hire you for while keeping the page visually led.', 'type' => 'Services'],
                    ['title' => 'Personal proof', 'summary' => 'Testimonials, media, and newsletter prompts support authority without clutter.', 'type' => 'Authority'],
                ],
                'ctaHeading' => 'Turn attention into the right enquiry',
                'ctaSummary' => 'Use portfolio when taste, trust, and a clear next step all need to be visible.',
            ],
            'saas' => [
                'summary' => 'A product-led SaaS homepage for dashboards, comparison, calculators, proof, and demo requests.',
                'heroHeading' => 'Make product value visible before the demo',
                'heroSummary' => 'Lead with outcome, interface context, proof, and clear paths into evaluation.',
                'featuresHeading' => 'A serious SaaS landing rhythm',
                'featuresSummary' => 'The theme supports product storytelling, evaluation content, and conversion without a marketing-site rebuild.',
                'features' => [
                    ['title' => 'Product proof', 'summary' => 'Dashboard media, metrics, and proof sections make the offer tangible.', 'type' => 'Product'],
                    ['title' => 'Evaluation paths', 'summary' => 'Comparison and directory sections guide buyers through use cases and objections.', 'type' => 'Buying'],
                    ['title' => 'Demo conversion', 'summary' => 'CTA and contact pages keep high-intent visitors moving.', 'type' => 'Pipeline'],
                ],
                'ctaHeading' => 'Give the sales motion a better public surface',
                'ctaSummary' => 'Use SaaS when product, proof, and demo conversion need to move together.',
            ],
            'ai-lab' => [
                'summary' => 'AI Lab demo content for Lumen AI.',
                'heroHeading' => 'Lumen-3: reasoning at the edge of the context window.',
                'heroSummary' => 'A multimodal foundation model with a 200k-token context window, built for long-horizon reasoning, tool use, and grounded answers. Run it in our hosted API or evaluate it against your own benchmark suite today.',
                'featuresHeading' => 'AI Lab sections',
                'featuresSummary' => 'Portable demo content using the AI Lab theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for AI Lab.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for AI Lab.', 'type' => 'Section'],
                    ['title' => 'model-cards', 'summary' => 'Theme-specific section for AI Lab.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with AI Lab',
                'ctaSummary' => 'Use this theme when the public site needs a focused ai lab presentation.',
            ],
            'api-platform' => [
                'summary' => 'API Platform demo content for Sonari API.',
                'heroHeading' => 'Launch a polished API Platform site',
                'heroSummary' => 'API Platform gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'API Platform sections',
                'featuresSummary' => 'Portable demo content using the API Platform theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for API Platform.', 'type' => 'Section'],
                    ['title' => 'code-hero', 'summary' => 'Theme-specific section for API Platform.', 'type' => 'Section'],
                    ['title' => 'quickstart', 'summary' => 'Theme-specific section for API Platform.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with API Platform',
                'ctaSummary' => 'Use this theme when the public site needs a focused api platform presentation.',
            ],
            'ai-agent' => [
                'summary' => 'AI Agent demo content for Aria Agents.',
                'heroHeading' => 'Resolve more, queue less.',
                'heroSummary' => 'Aria reads the ticket, gathers context from your tools, takes the action, and closes the loop — autonomously. Your team handles the hard 49%; Aria handles the rest.',
                'featuresHeading' => 'AI Agent sections',
                'featuresSummary' => 'Portable demo content using the AI Agent theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for AI Agent.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for AI Agent.', 'type' => 'Section'],
                    ['title' => 'outcome-metrics', 'summary' => 'Theme-specific section for AI Agent.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with AI Agent',
                'ctaSummary' => 'Use this theme when the public site needs a focused ai agent presentation.',
            ],
            'aeo-analytics' => [
                'summary' => 'AEO Analytics demo content for Visible AEO.',
                'heroHeading' => 'Your brand\'s answer-engine scoreboard.',
                'heroSummary' => 'Track every time ChatGPT, Perplexity, Gemini, and Copilot mention, cite, or recommend you — and see exactly where you\'re winning and where you\'re invisible.',
                'featuresHeading' => 'AEO Analytics sections',
                'featuresSummary' => 'Portable demo content using the AEO Analytics theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for AEO Analytics.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for AEO Analytics.', 'type' => 'Section'],
                    ['title' => 'dashboard-preview', 'summary' => 'Theme-specific section for AEO Analytics.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with AEO Analytics',
                'ctaSummary' => 'Use this theme when the public site needs a focused aeo analytics presentation.',
            ],
            'fintech-trust' => [
                'summary' => 'Fintech Trust demo content for Tessera Verify.',
                'heroHeading' => 'Business identity verification, built for regulators and revenue.',
                'heroSummary' => 'Verify a business in seconds — ownership, registration, sanctions, and risk — with an audit trail your compliance team and your auditors both trust.',
                'featuresHeading' => 'Fintech Trust sections',
                'featuresSummary' => 'Portable demo content using the Fintech Trust theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Fintech Trust.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Fintech Trust.', 'type' => 'Section'],
                    ['title' => 'compliance-badges', 'summary' => 'Theme-specific section for Fintech Trust.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Fintech Trust',
                'ctaSummary' => 'Use this theme when the public site needs a focused fintech trust presentation.',
            ],
            'crypto-defi' => [
                'summary' => 'Crypto DeFi demo content for Helix Protocol.',
                'heroHeading' => 'Put your assets to work, on-chain.',
                'heroSummary' => 'Helix is a permissionless lending market where deposits earn yield and borrowers tap instant liquidity — settled in seconds, secured by audited contracts.',
                'featuresHeading' => 'Crypto DeFi sections',
                'featuresSummary' => 'Portable demo content using the Crypto DeFi theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Crypto DeFi.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Crypto DeFi.', 'type' => 'Section'],
                    ['title' => 'protocol-stats', 'summary' => 'Theme-specific section for Crypto DeFi.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Crypto DeFi',
                'ctaSummary' => 'Use this theme when the public site needs a focused crypto defi presentation.',
            ],
            'quant-trading' => [
                'summary' => 'Quant Trading demo content for Meridian Quant.',
                'heroHeading' => 'Returns engineered by research, governed by risk.',
                'heroSummary' => 'Meridian runs a diversified book of systematic strategies across equities, futures, and FX — backed by transparent reporting and hard risk limits. Figures shown are illustrative.',
                'featuresHeading' => 'Quant Trading sections',
                'featuresSummary' => 'Portable demo content using the Quant Trading theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Quant Trading.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Quant Trading.', 'type' => 'Section'],
                    ['title' => 'performance-chart', 'summary' => 'Theme-specific section for Quant Trading.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Quant Trading',
                'ctaSummary' => 'Use this theme when the public site needs a focused quant trading presentation.',
            ],
            'devtool-oss' => [
                'summary' => 'Devtool OSS demo content for Scheduler OSS.',
                'heroHeading' => 'Launch a polished Devtool OSS site',
                'heroSummary' => 'Devtool OSS gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Devtool OSS sections',
                'featuresSummary' => 'Portable demo content using the Devtool OSS theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Devtool OSS.', 'type' => 'Section'],
                    ['title' => 'install-hero', 'summary' => 'Theme-specific section for Devtool OSS.', 'type' => 'Section'],
                    ['title' => 'github-proof', 'summary' => 'Theme-specific section for Devtool OSS.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Devtool OSS',
                'ctaSummary' => 'Use this theme when the public site needs a focused devtool oss presentation.',
            ],
            'robotics-hardware' => [
                'summary' => 'Robotics Hardware demo content for Atelier One.',
                'heroHeading' => 'Launch a polished Robotics Hardware site',
                'heroSummary' => 'Robotics Hardware gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Robotics Hardware sections',
                'featuresSummary' => 'Portable demo content using the Robotics Hardware theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Robotics Hardware.', 'type' => 'Section'],
                    ['title' => 'video-hero', 'summary' => 'Theme-specific section for Robotics Hardware.', 'type' => 'Section'],
                    ['title' => 'spec-sheet', 'summary' => 'Theme-specific section for Robotics Hardware.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Robotics Hardware',
                'ctaSummary' => 'Use this theme when the public site needs a focused robotics hardware presentation.',
            ],
            'manufacturing' => [
                'summary' => 'Manufacturing demo content for Forge Industries.',
                'heroHeading' => 'Launch a polished Manufacturing site',
                'heroSummary' => 'Manufacturing gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Manufacturing sections',
                'featuresSummary' => 'Portable demo content using the Manufacturing theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Manufacturing.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Manufacturing.', 'type' => 'Section'],
                    ['title' => 'capabilities-grid', 'summary' => 'Theme-specific section for Manufacturing.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Manufacturing',
                'ctaSummary' => 'Use this theme when the public site needs a focused manufacturing presentation.',
            ],
            'packaging-supplier' => [
                'summary' => 'Packaging Supplier demo content for Verda Packaging.',
                'heroHeading' => 'Launch a polished Packaging Supplier site',
                'heroSummary' => 'Packaging Supplier gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Packaging Supplier sections',
                'featuresSummary' => 'Portable demo content using the Packaging Supplier theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Packaging Supplier.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Packaging Supplier.', 'type' => 'Section'],
                    ['title' => 'product-range', 'summary' => 'Theme-specific section for Packaging Supplier.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Packaging Supplier',
                'ctaSummary' => 'Use this theme when the public site needs a focused packaging supplier presentation.',
            ],
            'conference-event' => [
                'summary' => 'Conference Event demo content for Forge Summit 2026.',
                'heroHeading' => 'Launch a polished Conference Event site',
                'heroSummary' => 'Conference Event gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Conference Event sections',
                'featuresSummary' => 'Portable demo content using the Conference Event theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Conference Event.', 'type' => 'Section'],
                    ['title' => 'event-hero', 'summary' => 'Theme-specific section for Conference Event.', 'type' => 'Section'],
                    ['title' => 'agenda', 'summary' => 'Theme-specific section for Conference Event.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Conference Event',
                'ctaSummary' => 'Use this theme when the public site needs a focused conference event presentation.',
            ],
            'podcast-show' => [
                'summary' => 'Podcast Show demo content for Signal & Noise.',
                'heroHeading' => 'Launch a polished Podcast Show site',
                'heroSummary' => 'Podcast Show gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Podcast Show sections',
                'featuresSummary' => 'Portable demo content using the Podcast Show theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Podcast Show.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Podcast Show.', 'type' => 'Section'],
                    ['title' => 'latest-episode', 'summary' => 'Theme-specific section for Podcast Show.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Podcast Show',
                'ctaSummary' => 'Use this theme when the public site needs a focused podcast show presentation.',
            ],
            'newsroom-magazine' => [
                'summary' => 'Newsroom Magazine demo content for The Mainframe.',
                'heroHeading' => 'Launch a polished Newsroom Magazine site',
                'heroSummary' => 'Newsroom Magazine gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Newsroom Magazine sections',
                'featuresSummary' => 'Portable demo content using the Newsroom Magazine theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Newsroom Magazine.', 'type' => 'Section'],
                    ['title' => 'featured-story', 'summary' => 'Theme-specific section for Newsroom Magazine.', 'type' => 'Section'],
                    ['title' => 'category-nav', 'summary' => 'Theme-specific section for Newsroom Magazine.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Newsroom Magazine',
                'ctaSummary' => 'Use this theme when the public site needs a focused newsroom magazine presentation.',
            ],
            'design-studio' => [
                'summary' => 'Design Studio demo content for Atelier Norð.',
                'heroHeading' => 'Launch a polished Design Studio site',
                'heroSummary' => 'Design Studio gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Design Studio sections',
                'featuresSummary' => 'Portable demo content using the Design Studio theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Design Studio.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Design Studio.', 'type' => 'Section'],
                    ['title' => 'project-gallery', 'summary' => 'Theme-specific section for Design Studio.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Design Studio',
                'ctaSummary' => 'Use this theme when the public site needs a focused design studio presentation.',
            ],
            'product-studio' => [
                'summary' => 'Product Studio demo content for Northbound Studio.',
                'heroHeading' => 'Launch a polished Product Studio site',
                'heroSummary' => 'Product Studio gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Product Studio sections',
                'featuresSummary' => 'Portable demo content using the Product Studio theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Product Studio.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Product Studio.', 'type' => 'Section'],
                    ['title' => 'tech-stack', 'summary' => 'Theme-specific section for Product Studio.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Product Studio',
                'ctaSummary' => 'Use this theme when the public site needs a focused product studio presentation.',
            ],
            'personal-dev' => [
                'summary' => 'Personal Dev demo content for Jonah Vance.',
                'heroHeading' => 'Launch a polished Personal Dev site',
                'heroSummary' => 'Personal Dev gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Personal Dev sections',
                'featuresSummary' => 'Portable demo content using the Personal Dev theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Personal Dev.', 'type' => 'Section'],
                    ['title' => 'about-intro', 'summary' => 'Theme-specific section for Personal Dev.', 'type' => 'Section'],
                    ['title' => 'writing-index', 'summary' => 'Theme-specific section for Personal Dev.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Personal Dev',
                'ctaSummary' => 'Use this theme when the public site needs a focused personal dev presentation.',
            ],
            'creator-newsletter' => [
                'summary' => 'Creator Newsletter demo content for The Long Game.',
                'heroHeading' => 'Launch a polished Creator Newsletter site',
                'heroSummary' => 'Creator Newsletter gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Creator Newsletter sections',
                'featuresSummary' => 'Portable demo content using the Creator Newsletter theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Creator Newsletter.', 'type' => 'Section'],
                    ['title' => 'subscribe-hero', 'summary' => 'Theme-specific section for Creator Newsletter.', 'type' => 'Section'],
                    ['title' => 'features', 'summary' => 'Theme-specific section for Creator Newsletter.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Creator Newsletter',
                'ctaSummary' => 'Use this theme when the public site needs a focused creator newsletter presentation.',
            ],
            'law-firm' => [
                'summary' => 'Law Firm demo content for Harlow & Finch LLP.',
                'heroHeading' => 'Launch a polished Law Firm site',
                'heroSummary' => 'Law Firm gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Law Firm sections',
                'featuresSummary' => 'Portable demo content using the Law Firm theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Law Firm.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Law Firm.', 'type' => 'Section'],
                    ['title' => 'practice-areas', 'summary' => 'Theme-specific section for Law Firm.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Law Firm',
                'ctaSummary' => 'Use this theme when the public site needs a focused law firm presentation.',
            ],
            'financial-advisory' => [
                'summary' => 'Financial Advisory demo content for Sterling & Vale Advisors.',
                'heroHeading' => 'Launch a polished Financial Advisory site',
                'heroSummary' => 'Financial Advisory gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Financial Advisory sections',
                'featuresSummary' => 'Portable demo content using the Financial Advisory theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Financial Advisory.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Financial Advisory.', 'type' => 'Section'],
                    ['title' => 'services', 'summary' => 'Theme-specific section for Financial Advisory.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Financial Advisory',
                'ctaSummary' => 'Use this theme when the public site needs a focused financial advisory presentation.',
            ],
            'construction-trades' => [
                'summary' => 'Construction Trades demo content for Granite Build Co.',
                'heroHeading' => 'Launch a polished Construction Trades site',
                'heroSummary' => 'Construction Trades gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Construction Trades sections',
                'featuresSummary' => 'Portable demo content using the Construction Trades theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Construction Trades.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Construction Trades.', 'type' => 'Section'],
                    ['title' => 'project-portfolio', 'summary' => 'Theme-specific section for Construction Trades.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Construction Trades',
                'ctaSummary' => 'Use this theme when the public site needs a focused construction trades presentation.',
            ],
            'fitness-wellness' => [
                'summary' => 'Fitness & Wellness demo content for Forge Fitness.',
                'heroHeading' => 'Train harder. Recover smarter.',
                'heroSummary' => 'A coached strength floor, 40+ studio classes a week, and recovery rooms under one roof in central Leeds. Book a free trial session and feel the difference in a week.',
                'featuresHeading' => 'Fitness & Wellness sections',
                'featuresSummary' => 'Portable demo content using the Fitness & Wellness theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Fitness & Wellness.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Fitness & Wellness.', 'type' => 'Section'],
                    ['title' => 'features', 'summary' => 'Theme-specific section for Fitness & Wellness.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Fitness & Wellness',
                'ctaSummary' => 'Use this theme when the public site needs a focused fitness & wellness presentation.',
            ],
            'beauty-spa' => [
                'summary' => 'Beauty & Spa demo content for Lumière Spa.',
                'heroHeading' => 'Slow down. You\'re due some care.',
                'heroSummary' => 'A boutique spa in the old town, with skin therapists, restorative massage, and a steam suite. Treat yourself, or someone you love, to an hour that resets everything.',
                'featuresHeading' => 'Beauty & Spa sections',
                'featuresSummary' => 'Portable demo content using the Beauty & Spa theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Beauty & Spa.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Beauty & Spa.', 'type' => 'Section'],
                    ['title' => 'features', 'summary' => 'Theme-specific section for Beauty & Spa.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Beauty & Spa',
                'ctaSummary' => 'Use this theme when the public site needs a focused beauty & spa presentation.',
            ],
            'travel-tourism' => [
                'summary' => 'Travel & Tourism demo content for Meridian Travel.',
                'heroHeading' => 'Go further, slower.',
                'heroSummary' => 'We design small-group and tailor-made journeys for travellers who want more than a checklist — local guides, honest pacing, and time to actually be somewhere. Tell us where you\'re dreaming of and we\'ll build the trip around you.',
                'featuresHeading' => 'Travel & Tourism sections',
                'featuresSummary' => 'Portable demo content using the Travel & Tourism theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Travel & Tourism.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Travel & Tourism.', 'type' => 'Section'],
                    ['title' => 'features', 'summary' => 'Theme-specific section for Travel & Tourism.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Travel & Tourism',
                'ctaSummary' => 'Use this theme when the public site needs a focused travel & tourism presentation.',
            ],
            'automotive-dealer' => [
                'summary' => 'Automotive Dealer demo content for Apex Motors.',
                'heroHeading' => 'Drive something you\'ll look back at.',
                'heroSummary' => 'A hand-picked selection of prestige and performance cars, every one inspected, prepared, and warrantied. Reserve online, view in our showroom, and drive away the same week.',
                'featuresHeading' => 'Automotive Dealer sections',
                'featuresSummary' => 'Portable demo content using the Automotive Dealer theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Automotive Dealer.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Automotive Dealer.', 'type' => 'Section'],
                    ['title' => 'features', 'summary' => 'Theme-specific section for Automotive Dealer.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Automotive Dealer',
                'ctaSummary' => 'Use this theme when the public site needs a focused automotive dealer presentation.',
            ],
            'property-developer' => [
                'summary' => 'Property Developer demo content for Crestwood Developments.',
                'heroHeading' => 'Homes designed for the way you actually live.',
                'heroSummary' => 'We build characterful new homes and apartments in well-connected places, with the specification right and the detail considered. Explore our current developments and register your interest to hear about new releases first.',
                'featuresHeading' => 'Property Developer sections',
                'featuresSummary' => 'Portable demo content using the Property Developer theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Property Developer.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Property Developer.', 'type' => 'Section'],
                    ['title' => 'features', 'summary' => 'Theme-specific section for Property Developer.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Property Developer',
                'ctaSummary' => 'Use this theme when the public site needs a focused property developer presentation.',
            ],
            'recruitment-jobs' => [
                'summary' => 'Recruitment & Jobs demo content for Beacon Talent.',
                'heroHeading' => 'Find your next role. Or your next hire.',
                'heroSummary' => 'We\'re a specialist recruitment team working across Tech, Finance, and Healthcare. We take the time to understand the role and the person — so candidates land somewhere they fit, and employers hire people who stay.',
                'featuresHeading' => 'Recruitment & Jobs sections',
                'featuresSummary' => 'Portable demo content using the Recruitment & Jobs theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Recruitment & Jobs.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Recruitment & Jobs.', 'type' => 'Section'],
                    ['title' => 'features', 'summary' => 'Theme-specific section for Recruitment & Jobs.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Recruitment & Jobs',
                'ctaSummary' => 'Use this theme when the public site needs a focused recruitment & jobs presentation.',
            ],
            'blog' => [
                'summary' => 'Blog demo content for Quarter Press.',
                'heroHeading' => 'Launch a polished Blog site',
                'heroSummary' => 'Blog gives Capell sites a focused frontend theme with portable content, safe public output, and theme-specific visual tokens.',
                'featuresHeading' => 'Blog sections',
                'featuresSummary' => 'Portable demo content using the Blog theme profile.',
                'features' => [
                    ['title' => 'navigation', 'summary' => 'Theme-specific section for Blog.', 'type' => 'Section'],
                    ['title' => 'hero', 'summary' => 'Theme-specific section for Blog.', 'type' => 'Section'],
                    ['title' => 'features', 'summary' => 'Theme-specific section for Blog.', 'type' => 'Section'],
                ],
                'ctaHeading' => 'Launch with Blog',
                'ctaSummary' => 'Use this theme when the public site needs a focused editorial serif presentation.',
            ],
        ];

        return array_replace($defaultProfile, $profiles[$themeKey] ?? []);
    }

    /**
     * @return array<int, array{title: string, summary: string, type: string}>
     */
    private function defaultSpotlightItems(string $themeName): array
    {
        return [
            [
                'title' => sprintf('%s first impression', $themeName),
                'summary' => "Show the theme's strongest hero, media, and proof treatment as one focused buyer-facing story.",
                'type' => 'Moment',
            ],
            [
                'title' => sprintf('%s content depth', $themeName),
                'summary' => 'Use tabbed panels to preview how directory, detail, resource, or product content carries the same premium system.',
                'type' => 'Depth',
            ],
            [
                'title' => sprintf('%s conversion route', $themeName),
                'summary' => 'Keep contact, CTA, proof, and next-step copy close together so the theme feels complete beyond the first scroll.',
                'type' => 'Action',
            ],
            [
                'title' => sprintf('%s editor handoff', $themeName),
                'summary' => 'The layout stays interactive while the content remains portable render data that editors can safely own.',
                'type' => 'Governance',
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, summary: string, type: string}>
     */
    private function defaultGalleryItems(string $themeName): array
    {
        return [
            [
                'title' => sprintf('%s homepage rhythm', $themeName),
                'summary' => 'Hero, proof, gallery, content, and CTA sections work together as a launch-ready page.',
                'type' => 'Homepage',
            ],
            [
                'title' => sprintf('%s content surface', $themeName),
                'summary' => 'Directory and detail previews give editors realistic surfaces beyond the first screen.',
                'type' => 'Content',
            ],
            [
                'title' => sprintf('%s conversion path', $themeName),
                'summary' => 'Contact, CTA, and empty-state pages keep visitor journeys complete across the theme.',
                'type' => 'Conversion',
            ],
            [
                'title' => sprintf('%s media system', $themeName),
                'summary' => 'Carousel-ready media creates premium movement while staying data-driven and reusable.',
                'type' => 'Gallery',
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, summary: string, type: string}>
     */
    private function defaultPathwayItems(string $themeName): array
    {
        return [
            [
                'title' => 'Start with the homepage',
                'summary' => sprintf('Use the %s first screen, feature rhythm, gallery, and proof sections as a complete launchable starting point.', $themeName),
                'type' => 'Launch',
            ],
            [
                'title' => 'Add directory depth',
                'summary' => 'Turn services, courses, resources, products, campaigns, or work into browsable cards without custom page code.',
                'type' => 'Browse',
            ],
            [
                'title' => 'Connect conversion pages',
                'summary' => 'Pair CTA, contact, and empty-state surfaces so the theme handles real visitor journeys, not just visual previews.',
                'type' => 'Convert',
            ],
            [
                'title' => 'Keep content portable',
                'summary' => 'The theme owns presentation while the public route consumes safe, hydrated render data from Capell records.',
                'type' => 'Govern',
            ],
        ];
    }

    /**
     * @return array<int, array{metric: string, name: string, quote: string}>
     */
    private function defaultProofItems(string $themeName): array
    {
        return [
            [
                'metric' => '7 public surfaces',
                'name' => sprintf('%s preview set', $themeName),
                'quote' => 'Homepage, directory, detail, contact, empty, CTA, and not-found pages render from one portable content model.',
            ],
            [
                'metric' => '4 media patterns',
                'name' => 'Richer preview system',
                'quote' => 'Hero, feature cards, gallery carousel, and proof media make the theme feel closer to a paid package.',
            ],
            [
                'metric' => '0 editor leaks',
                'name' => 'Public-safe rendering',
                'quote' => 'The public route consumes hydrated render data without exposing authoring metadata or admin concerns.',
            ],
        ];
    }

    /**
     * @param  array<int, string>  $imageUrls
     * @param  array<int, array{metric: string, name: string, quote: string}>  $copy
     * @return array<int, array<string, string>>
     */
    private function proof(array $imageUrls, array $copy = []): array
    {
        return array_map(
            static fn (string $imageUrl, int $index): array => [
                'metric' => $copy[$index]['metric'] ?? sprintf('%d ready surfaces', $index + 4),
                'name' => $copy[$index]['name'] ?? 'Preview proof',
                'quote' => $copy[$index]['quote'] ?? 'Structured proof data with portable media.',
                'image' => $imageUrl,
            ],
            $imageUrls,
            array_keys($imageUrls),
        );
    }
}
