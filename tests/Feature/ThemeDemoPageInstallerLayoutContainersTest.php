<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageInstaller;
use Capell\LayoutBuilder\Actions\ResolveLayoutAreaContainersAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;

final class ThemeDemoPageInstallerLayoutContainersFixtureProvider implements ProvidesThemeDemoContent
{
    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [
            new ThemeDemoPageDefinition(
                surface: 'homepage',
                name: $themeName . ' Home',
                title: $themeName . ' Homepage',
                slug: 'theme-' . $themeKey,
                content: '<p>Layout builder demo homepage.</p>',
                renderData: [
                    'summary' => 'Homepage render data that should be dropped once containers take over.',
                    'sections' => [
                        ['type' => 'hero', 'heading' => 'Legacy section pipeline'],
                    ],
                ],
                type: PageTypeEnum::Home,
                layout: LayoutEnum::Home,
                containers: [
                    'main' => [
                        'meta' => ['colspan' => 12],
                        'widgets' => [
                            ['widget_key' => 'page-content'],
                        ],
                    ],
                ],
                widgets: [
                    ['method' => 'pageContentWidget'],
                ],
            ),
        ];
    }
}

final class ThemeDemoPageInstallerUnknownWidgetMethodFixtureProvider implements ProvidesThemeDemoContent
{
    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [
            new ThemeDemoPageDefinition(
                surface: 'homepage',
                name: $themeName . ' Home',
                title: $themeName . ' Homepage',
                slug: 'theme-' . $themeKey,
                content: '<p>Layout builder demo homepage.</p>',
                renderData: [
                    'summary' => 'Homepage render data that should be dropped once containers take over.',
                    'sections' => [
                        ['type' => 'hero', 'heading' => 'Legacy section pipeline'],
                    ],
                ],
                type: PageTypeEnum::Home,
                layout: LayoutEnum::Home,
                containers: [
                    'main' => [
                        'meta' => ['colspan' => 12],
                        'widgets' => [
                            ['widget_key' => 'page-content'],
                        ],
                    ],
                ],
                widgets: [
                    ['method' => 'thisMethodDoesNotExistOnWidgetCreator'],
                ],
            ),
        ];
    }
}

final class ThemeDemoPageInstallerSharedLayoutFixtureProvider implements ProvidesThemeDemoContent
{
    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [
            new ThemeDemoPageDefinition(
                surface: 'detail',
                name: $themeName . ' Detail',
                title: $themeName . ' Detail - Browser title suffix',
                slug: 'detail',
                content: '<p>Detail content.</p>',
                renderData: [
                    'summary' => 'Detail render data that should be dropped once containers take over.',
                    'sections' => [
                        ['type' => 'hero', 'heading' => 'Legacy detail section pipeline'],
                    ],
                ],
                containers: [
                    'main' => [
                        'widgets' => [
                            ['widget_key' => 'page-content', 'occurrence' => 1],
                            ['widget_key' => 'detail-widget', 'occurrence' => 1],
                        ],
                    ],
                ],
                widgets: [
                    ['method' => 'pageContentWidget'],
                    ['method' => 'bespokeContentWidget', 'args' => [
                        'detail-widget',
                        'Detail widget',
                        'capell.widget.page-content',
                        ['heading' => 'Detail widget'],
                    ]],
                ],
            ),
            new ThemeDemoPageDefinition(
                surface: 'cta',
                name: $themeName . ' CTA',
                title: $themeName . ' CTA - Browser title suffix',
                slug: 'cta',
                content: '<p>CTA content.</p>',
                renderData: [
                    'summary' => 'CTA render data that should be dropped once containers take over.',
                    'sections' => [
                        ['type' => 'cta', 'heading' => 'Legacy cta section pipeline'],
                    ],
                ],
                containers: [
                    'main' => [
                        'widgets' => [
                            ['widget_key' => 'page-content', 'occurrence' => 1],
                            ['widget_key' => 'cta-widget', 'occurrence' => 1],
                        ],
                    ],
                ],
                widgets: [
                    ['method' => 'pageContentWidget'],
                    ['method' => 'bespokeContentWidget', 'args' => [
                        'cta-widget',
                        'CTA widget',
                        'capell.widget.page-content',
                        ['heading' => 'CTA widget'],
                    ]],
                ],
            ),
        ];
    }
}

final class ThemeDemoPageInstallerContactFixtureProvider implements ProvidesThemeDemoContent
{
    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [
            new ThemeDemoPageDefinition(
                surface: 'contact',
                name: $themeName . ' Contact',
                title: $themeName . ' Contact',
                slug: 'contact',
                content: '<h2>Contact hero</h2><p>Contact content.</p>',
                renderData: [
                    'summary' => 'Contact render data without a provider-defined form.',
                    'sections' => [
                        ['type' => 'hero', 'heading' => 'Contact hero'],
                    ],
                ],
                layout: LayoutEnum::System,
                containers: [
                    'main' => [
                        'widgets' => [
                            ['widget_key' => 'page-content'],
                        ],
                    ],
                ],
                widgets: [
                    ['method' => 'pageContentWidget'],
                ],
            ),
        ];
    }
}

final class ThemeDemoPageInstallerEmptySearchFixtureProvider implements ProvidesThemeDemoContent
{
    /**
     * @return array<int, ThemeDemoPageDefinition>
     */
    public function definitions(string $themeKey, string $themeName, string $baseUrl): array
    {
        return [
            new ThemeDemoPageDefinition(
                surface: 'empty',
                name: $themeName . ' Empty',
                title: $themeName . ' Empty',
                slug: 'empty',
                content: '<p>Empty content.</p>',
                renderData: [
                    'summary' => 'Empty render data without a provider-defined search section.',
                    'sections' => [
                        ['type' => 'hero', 'heading' => 'No results'],
                        ['type' => 'cta', 'heading' => 'Browse instead'],
                    ],
                ],
            ),
        ];
    }
}

it('seeds layout builder containers and widgets from a demo page definition', function (): void {
    $themeKey = 'layout-containers-demo';

    ThemeDemoPageInstaller::run(
        data: new ThemeDemoInstallData(
            siteNames: ['Layout Containers Demo'],
            languageCodes: ['en'],
            baseUrl: 'https://layout-containers-demo.test',
        ),
        themeKey: $themeKey,
        themeName: 'Layout Containers Demo',
        contentProvider: new ThemeDemoPageInstallerLayoutContainersFixtureProvider,
    );

    $page = Page::query()->where('name', 'Layout Containers Demo Home')->firstOrFail();
    $layout = $page->layout;

    throw_unless($layout instanceof Layout, RuntimeException::class, 'Expected the demo page to have a layout.');

    expect(Widget::query()->where('key', 'page-content')->exists())->toBeTrue();
    expect($layout->key)->toContain('homepage');

    $mainContainers = app(ResolveLayoutAreaContainersAction::class)->handle($layout->containers, LayoutAreaRegistry::MAIN);

    expect($mainContainers)->toHaveKey('main')
        ->and($mainContainers['main']['widgets'])->toBe([
            ['widget_key' => 'page-content'],
        ]);

    expect(data_get($page->meta, 'theme_demo.render_data'))->not->toHaveKey('sections');
});

it('does not clobber an existing layout with containers unless forced', function (): void {
    $themeKey = 'layout-containers-demo-existing';

    $layout = Layout::query()->where('key', LayoutEnum::Home->value)->first()
        ?? Layout::factory()->create(['key' => LayoutEnum::Home->value]);

    $preExistingContainers = [
        'main' => [
            'meta' => ['colspan' => 12],
            'widgets' => [
                ['widget_key' => 'breadcrumbs'],
            ],
        ],
    ];

    $layout->update(['containers' => $preExistingContainers]);

    ThemeDemoPageInstaller::run(
        data: new ThemeDemoInstallData(
            siteNames: ['Layout Containers Demo Existing'],
            languageCodes: ['en'],
            baseUrl: 'https://layout-containers-demo-existing.test',
        ),
        themeKey: $themeKey,
        themeName: 'Layout Containers Demo Existing',
        contentProvider: new ThemeDemoPageInstallerLayoutContainersFixtureProvider,
    );

    expect($layout->refresh()->containers)->toBe($preExistingContainers);
});

it('creates page-specific container layouts for demo surfaces sharing the same base layout', function (): void {
    $themeKey = 'layout-containers-demo-shared-layouts';

    ThemeDemoPageInstaller::run(
        data: new ThemeDemoInstallData(
            siteNames: ['Layout Containers Demo Shared Layouts'],
            languageCodes: ['en'],
            baseUrl: 'https://layout-containers-demo-shared-layouts.test',
        ),
        themeKey: $themeKey,
        themeName: 'Layout Containers Demo Shared Layouts',
        contentProvider: new ThemeDemoPageInstallerSharedLayoutFixtureProvider,
    );

    $detail = Page::query()->where('name', 'Layout Containers Demo Shared Layouts Detail')->firstOrFail();
    $cta = Page::query()->where('name', 'Layout Containers Demo Shared Layouts CTA')->firstOrFail();

    expect($detail->layout_id)->not->toBe($cta->layout_id)
        ->and($detail->layout?->key)->toContain('detail')
        ->and($cta->layout?->key)->toContain('cta')
        ->and($detail->translation?->getMeta('hero_title'))->toBe('Legacy detail section pipeline')
        ->and($cta->translation?->getMeta('hero_title'))->toBe('Layout Containers Demo Shared Layouts CTA - Browser title suffix')
        ->and(data_get($detail->layout?->containers, 'main.widgets'))->toBe([
            ['widget_key' => 'page-content', 'occurrence' => 1],
            ['widget_key' => 'detail-widget', 'occurrence' => 1],
        ])
        ->and(data_get($cta->layout?->containers, 'main.widgets'))->toBe([
            ['widget_key' => 'page-content', 'occurrence' => 1],
            ['widget_key' => 'cta-widget', 'occurrence' => 1],
        ]);

    expect(Widget::query()->where('key', 'detail-widget')->exists())->toBeTrue()
        ->and(Widget::query()->where('key', 'cta-widget')->exists())->toBeTrue();
});

it('adds the standard contact form render data when a contact demo page omits it', function (): void {
    $themeKey = 'layout-containers-demo-contact';

    ThemeDemoPageInstaller::run(
        data: new ThemeDemoInstallData(
            siteNames: ['Layout Containers Demo Contact'],
            languageCodes: ['en'],
            baseUrl: 'https://layout-containers-demo-contact.test',
        ),
        themeKey: $themeKey,
        themeName: 'Layout Containers Demo Contact',
        contentProvider: new ThemeDemoPageInstallerContactFixtureProvider,
    );

    $page = Page::query()->where('name', 'Layout Containers Demo Contact Contact')->firstOrFail();

    expect(data_get($page->meta, 'theme_demo.render_data.sections'))->toBeNull()
        ->and(data_get($page->meta, 'theme_demo.render_data.form.id'))->toBe('theme-demo-contact-form')
        ->and(data_get($page->meta, 'theme_demo.render_data.form.fields'))->toHaveCount(4)
        ->and($page->translation?->content)->toBe('<p>Contact content.</p>');
});

it('adds a search recovery section to empty demo pages when one is omitted', function (): void {
    $themeKey = 'layout-containers-demo-empty-search';

    ThemeDemoPageInstaller::run(
        data: new ThemeDemoInstallData(
            siteNames: ['Layout Containers Demo Empty Search'],
            languageCodes: ['en'],
            baseUrl: 'https://layout-containers-demo-empty-search.test',
        ),
        themeKey: $themeKey,
        themeName: 'Layout Containers Demo Empty Search',
        contentProvider: new ThemeDemoPageInstallerEmptySearchFixtureProvider,
    );

    $page = Page::query()->where('name', 'Layout Containers Demo Empty Search Empty')->firstOrFail();
    $sections = data_get($page->meta, 'theme_demo.render_data.sections', []);
    $sectionTypes = is_array($sections) ? array_column($sections, 'type') : [];

    expect($sectionTypes)->toBe(['hero', 'search', 'cta'])
        ->and(data_get($page->meta, 'theme_demo.render_data.sections.1.query'))->toBe('no matching results')
        ->and(data_get($page->meta, 'theme_demo.render_data.sections.1.results'))->toBe([]);
});

it('throws when a widget blueprint references an unknown WidgetCreator method', function (): void {
    $themeKey = 'layout-containers-demo-unknown-method';

    ThemeDemoPageInstaller::run(
        data: new ThemeDemoInstallData(
            siteNames: ['Layout Containers Demo Unknown Method'],
            languageCodes: ['en'],
            baseUrl: 'https://layout-containers-demo-unknown-method.test',
        ),
        themeKey: $themeKey,
        themeName: 'Layout Containers Demo Unknown Method',
        contentProvider: new ThemeDemoPageInstallerUnknownWidgetMethodFixtureProvider,
    );
})->throws(InvalidArgumentException::class, 'WidgetCreator has no method [thisMethodDoesNotExistOnWidgetCreator] for demo widget blueprint.');
