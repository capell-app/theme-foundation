<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Core\Support\Creator\LayoutCreator;
use Capell\FoundationTheme\Data\ThemeDemoInstallData;
use Capell\FoundationTheme\Support\Demo\FoundationDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageInstaller;
use Capell\FoundationTheme\Tests\Fixtures\ThemeDemoPageInstallerContactFixtureProvider;
use Capell\FoundationTheme\Tests\Fixtures\ThemeDemoPageInstallerEmptySearchFixtureProvider;
use Capell\FoundationTheme\Tests\Fixtures\ThemeDemoPageInstallerIdentityFixtureProvider;
use Capell\FoundationTheme\Tests\Fixtures\ThemeDemoPageInstallerLayoutContainersFixtureProvider;
use Capell\FoundationTheme\Tests\Fixtures\ThemeDemoPageInstallerSharedLayoutFixtureProvider;
use Capell\FoundationTheme\Tests\Fixtures\ThemeDemoPageInstallerUnknownWidgetMethodFixtureProvider;
use Capell\LayoutBuilder\Actions\ResolveLayoutAreaContainersAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutAreas\LayoutAreaRegistry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;

it('leaves ambiguous identities unchanged when Core has no mutable match', function (): void {
    $data = new ThemeDemoInstallData(['Ambiguous Identity'], ['en'], 'https://ambiguous.test');
    foreach (['first', 'second'] as $surface) {
        ThemeDemoPageInstaller::run($data, 'ambiguous', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider($surface, surface: $surface));
        $page = Page::query()->where('name', $surface)->firstOrFail();
        $meta = $page->meta ?? [];
        Arr::set($meta, 'theme_demo.surface', 'detail');
        $page->update(['meta' => $meta]);
    }

    $before = Page::query()->orderBy('id')->get()->map->getAttributes()->all();

    expect(ThemeDemoPageInstaller::run($data, 'ambiguous', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('New definition')))->toBe(0)
        ->and(Page::query()->count())->toBe(3)
        ->and(Page::query()->orderBy('id')->limit(2)->get()->map->getAttributes()->all())->toBe($before)
        ->and(Page::query()->where('name', 'New definition')->sole()->pageUrls()->whereNull('type')->firstOrFail()->url)->toBe('/theme-ambiguous-detail');
});

it('rolls back identity or legacy layout alignment when translation persistence fails', function (bool $legacy): void {
    $data = new ThemeDemoInstallData(['Atomic Identity'], ['en'], 'https://atomic.test');
    ThemeDemoPageInstaller::run($data, 'atomic', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Original'));
    $page = Page::query()->sole();
    $layout = resolve(LayoutCreator::class)->create(LayoutEnum::Home);
    $name = $legacy ? 'Original' : 'Updated';
    if ($legacy) {
        $meta = $page->meta ?? [];
        Arr::forget($meta, 'theme_demo.surface');
        $page->update(['meta' => $meta]);
    }

    $before = $page->refresh()->getAttributes();
    $translation = $page->translations()->firstOrFail();
    $translationBefore = $translation->getAttributes();
    Event::listen('eloquent.saving: ' . $translation::class, function (Translation $saving) use ($page, $name, $layout): void {
        if ($saving->translatable_type !== $page->getMorphClass() || $saving->translatable_id !== $page->id) {
            return;
        }

        $aligned = Page::query()->findOrFail($page->id);
        expect($aligned->name)->toBe($name)
            ->and($aligned->layout_id)->toBe($layout->id);
        throw new RuntimeException('Translation persistence refused.');
    });

    expect(fn (): int => ThemeDemoPageInstaller::run($data, 'atomic', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider($name, LayoutEnum::Home)))
        ->toThrow(RuntimeException::class, 'Translation persistence refused.');
    expect($page->refresh()->getAttributes())->toBe($before)
        ->and($translation->refresh()->getAttributes())->toBe($translationBefore)
        ->and(Page::query()->count())->toBe(1);
})->with([false, true]);

it('does not repair layouts of another theme or surface sharing a name', function (string $themeKey, string $surface): void {
    $data = new ThemeDemoInstallData(['Owned Identity'], ['en'], 'https://ownership.test');
    ThemeDemoPageInstaller::run($data, 'owned', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Shared name'));
    $page = Page::query()->sole();
    $before = $page->getAttributes();
    resolve(LayoutCreator::class)->create(LayoutEnum::Home);

    expect(ThemeDemoPageInstaller::run($data, $themeKey, 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Shared name', LayoutEnum::Home, $surface)))->toBe(0)
        ->and(Page::query()->count())->toBe(2)
        ->and($page->refresh()->getAttributes())->toBe($before);
})->with([['other-theme', 'detail'], ['owned', 'other-surface']]);

it('never aligns a soft-deleted demo identity', function (): void {
    $data = new ThemeDemoInstallData(['Deleted Identity'], ['en'], 'https://deleted.test', force: true);
    ThemeDemoPageInstaller::run($data, 'deleted', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Original', slug: 'old'));
    $page = Page::query()->sole();
    $page->delete();

    $before = $page->refresh()->getAttributes();

    expect(ThemeDemoPageInstaller::run($data, 'deleted', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Updated', LayoutEnum::Home, slug: 'new')))->toBe(0)
        ->and(Page::query()->count())->toBe(1)
        ->and(Page::query()->sole()->id)->not->toBe($page->id)
        ->and($page->refresh()->getAttributes())->toBe($before)
        ->and($page->trashed())->toBeTrue();
});

it('preserves the base mutable lookup when legacy pages have duplicate identities', function (): void {
    $data = new ThemeDemoInstallData(['Legacy Duplicate Identity'], ['en'], 'https://duplicate.test');
    ThemeDemoPageInstaller::run($data, 'duplicate', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Original', surface: 'legacy', slug: 'old'));
    $original = Page::query()->where('name', 'Original')->firstOrFail();
    ThemeDemoPageInstaller::run($data, 'duplicate', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Updated', slug: 'new'));
    $updated = Page::query()->where('name', 'Updated')->firstOrFail();
    $meta = $original->meta ?? [];
    Arr::set($meta, 'theme_demo.surface', 'detail');
    $original->update(['meta' => $meta]);
    $originalAttributes = $original->refresh()->getAttributes();
    $canonicalId = $updated->pageUrls()->whereNull('type')->firstOrFail()->id;

    expect(ThemeDemoPageInstaller::run($data, 'duplicate', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Updated', slug: 'new')))->toBe(0)
        ->and(Page::query()->count())->toBe(2)
        ->and($original->refresh()->getAttributes())->toBe($originalAttributes)
        ->and($original->pageUrls()->whereNull('type')->firstOrFail()->url)->toBe('/old')
        ->and($updated->refresh()->name)->toBe('Updated')
        ->and($updated->pageUrls()->whereNull('type')->firstOrFail()->id)->toBe($canonicalId)
        ->and($updated->pageUrls()->whereNull('type')->firstOrFail()->url)->toBe('/new');
});

it('preserves the base layout repair when legacy identity metadata is missing', function (string $missingMarker): void {
    $data = new ThemeDemoInstallData(['Legacy Missing Identity'], ['en'], 'https://legacy.test');
    $provider = new ThemeDemoPageInstallerIdentityFixtureProvider('Expected name');
    ThemeDemoPageInstaller::run($data, 'legacy', 'Identity', $provider);
    $page = Page::query()->where('name', 'Expected name')->firstOrFail();
    $canonical = $page->pageUrls()->whereNull('type')->firstOrFail();
    $canonicalId = $canonical->id;
    $meta = $page->meta ?? [];
    Arr::forget($meta, 'theme_demo.' . $missingMarker);
    $oldLayout = resolve(LayoutCreator::class)->create(LayoutEnum::Home);
    $page->update(['meta' => $meta, 'layout_id' => $oldLayout->id]);

    expect(ThemeDemoPageInstaller::run($data, 'legacy', 'Identity', $provider))->toBe(0)
        ->and(Page::query()->count())->toBe(1)
        ->and(Page::query()->sole()->id)->toBe($page->id)
        ->and($page->refresh()->layout?->key)->toBe(LayoutEnum::Default->value)
        ->and($page->pageUrls()->whereNull('type')->firstOrFail()->id)->toBe($canonicalId)
        ->and($canonical->refresh()->url)->toBe('/theme-legacy-detail');
})->with(['surface', 'theme_key']);

it('retains seeded page identity when the definition name or layout changes', function (LayoutEnum $layout): void {
    $data = new ThemeDemoInstallData(['Stable Identity One', 'Stable Identity Two'], ['en'], 'https://identity.test', force: true);
    foreach (['identity-one', 'identity-two'] as $themeKey) {
        ThemeDemoPageInstaller::run($data, $themeKey, 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Original ' . $themeKey));
    }

    $pages = Page::query()->where('meta->theme_demo->theme_key', 'identity-one')->orderBy('site_id')->get();
    $otherIds = Page::query()->where('meta->theme_demo->theme_key', 'identity-two')->orderBy('site_id')->pluck('id')->all();
    $ids = $pages->modelKeys();
    expect($pages)->toHaveCount(2);

    ThemeDemoPageInstaller::run($data, 'identity-one', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Updated display name', $layout));

    $updated = Page::query()->with('layout')->where('meta->theme_demo->theme_key', 'identity-one')->orderBy('site_id')->get();
    expect($updated->modelKeys())->toBe($ids)
        ->and($updated)->toHaveCount(2)
        ->and(Page::query()->where('meta->theme_demo->theme_key', 'identity-two')->orderBy('site_id')->pluck('id')->all())->toBe($otherIds);
    foreach ($updated as $page) {
        expect($page->name)->toBe('Updated display name')
            ->and($page->layout?->key)->toBe($layout->value)
            ->and($page->translations()->firstOrFail()->title)->toBe('Updated display name')
            ->and($page->pageUrls()->whereNull('type')->count())->toBe(1)
            ->and($page->pageUrls()->whereNull('type')->firstOrFail()->url)->toBe('/theme-identity-one-detail');
    }
})->with([LayoutEnum::Default, LayoutEnum::Home]);

it('keeps demo surfaces with the same display name on separate pages', function (): void {
    $data = new ThemeDemoInstallData(['Shared Name Identity'], ['en'], 'https://shared-name.test');
    ThemeDemoPageInstaller::run($data, 'shared-name', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Shared display name', surface: 'detail', withContainers: true));
    $detail = Page::query()->where('meta->theme_demo->surface', 'detail')->firstOrFail();
    $detailLayoutId = $detail->layout_id;

    ThemeDemoPageInstaller::run($data, 'shared-name', 'Identity', new ThemeDemoPageInstallerIdentityFixtureProvider('Shared display name', surface: 'calendar-day', withContainers: true));

    expect(Page::query()->where('meta->theme_demo->theme_key', 'shared-name')->count())->toBe(2)
        ->and($detail->refresh()->layout_id)->toBe($detailLayoutId)
        ->and(data_get($detail->meta, 'theme_demo.surface'))->toBe('detail');
    $day = Page::query()->where('meta->theme_demo->surface', 'calendar-day')->firstOrFail();
    expect($day->id)->not->toBe($detail->id)
        ->and($day->layout_id)->not->toBe($detailLayoutId);
});

it('installs Foundation brand navigation and footer around each page content', function (): void {
    $provider = new FoundationDemoContent;
    ThemeDemoPageInstaller::run(
        data: new ThemeDemoInstallData(
            siteNames: ['Foundation Chrome'],
            languageCodes: ['en'],
            baseUrl: 'https://foundation-chrome.test',
        ),
        themeKey: 'foundation',
        themeName: 'Foundation',
        contentProvider: $provider,
    );

    foreach ($provider->definitions('foundation', 'Foundation', 'https://foundation-chrome.test') as $definition) {
        $page = Page::query()->where('name', $definition->name)->firstOrFail();
        $layout = $page->layout;
        throw_unless($layout instanceof Layout, RuntimeException::class, 'Expected a Foundation layout.');
        $containers = ResolveLayoutAreaContainersAction::run($layout->containers, LayoutAreaRegistry::MAIN);
        $widgets = data_get($containers, 'main.widgets');
        throw_unless(is_array($widgets), RuntimeException::class, 'Expected Foundation main widgets.');
        $keys = array_column($widgets, 'widget_key');

        expect($layout->meta)->toHaveKey('header', false)->toHaveKey('footer', false)
            ->and($keys[0] ?? null)->toBe('foundation-navigation-' . $definition->surface . '-1')
            ->and(end($keys))->toBe('foundation-footer-' . $definition->surface . '-1')
            ->and($keys)->toContain('page-content');
    }
});

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
    expect($layout->meta)
        ->toHaveKey('header', false)
        ->toHaveKey('footer', false);

    $mainContainers = ResolveLayoutAreaContainersAction::run($layout->containers, LayoutAreaRegistry::MAIN);

    expect($mainContainers)->toHaveKey('main')
        ->and($mainContainers['main']['widgets'])->toBe([
            ['widget_key' => 'page-content'],
            ['widget_key' => 'navigation-widget', 'occurrence' => 1],
            ['widget_key' => 'footer-widget', 'occurrence' => 1],
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
