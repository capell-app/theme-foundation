<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;
use Capell\Core\Models\Layout;
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

    $layout = Layout::query()->where('key', LayoutEnum::Home->value)->firstOrFail();

    expect(Widget::query()->where('key', 'page-content')->exists())->toBeTrue();

    $mainContainers = ResolveLayoutAreaContainersAction::run($layout->containers, LayoutAreaRegistry::MAIN);

    expect($mainContainers)->toHaveKey('main')
        ->and($mainContainers['main']['widgets'])->toBe([
            ['widget_key' => 'page-content'],
        ]);

    $page = $layout->pages()->first();

    expect($page)->not->toBeNull()
        ->and($page->meta['theme_demo']['render_data'])->not->toHaveKey('sections');
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
