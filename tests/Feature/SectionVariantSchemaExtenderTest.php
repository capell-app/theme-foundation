<?php

declare(strict_types=1);

use Awcodes\Curator\CuratorServiceProvider;
use Capell\Admin\Enums\CapellPermission;
use Capell\Blog\Models\Article;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Packages\InstalledRuntimeLifecycle;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\Events\Models\Event;
use Capell\FoundationTheme\Actions\ResolveSectionVariantScopeAction;
use Capell\FoundationTheme\Filament\Extenders\SectionVariantSchemaExtender;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\FoundationTheme\Support\SectionVariantViewResolver;
use Capell\Frontend\Data\FrontendWork;
use Capell\Frontend\Support\Kernel\Steps\ThemeResolverStep;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\ThemeEditorial\EditorialThemeServiceProvider;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Livewire\Livewire;

use function Pest\Laravel\get;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

function bootEditorialSectionVariantLifecycle(): void
{
    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);
    CapellCore::forcePackageInstalled(EditorialThemeServiceProvider::$packageName);

    resolve(InstalledRuntimeLifecycle::class)->refresh(FoundationThemeServiceProvider::$packageName);

    $editorialProvider = new EditorialThemeServiceProvider(app());
    $editorialProvider->register();
    $editorialProvider->boot(resolve(ThemeRegistry::class));
}

function activateEditorialTheme(): Theme
{
    Theme::query()->update(['default' => false]);

    return Theme::query()->updateOrCreate(
        ['key' => EditorialThemeServiceProvider::THEME_KEY],
        [
            'name' => EditorialThemeServiceProvider::definition()->name,
            'blueprint_id' => Blueprint::factory()->theme()->create()->getKey(),
            'default' => true,
            'status' => true,
        ],
    );
}

it('is tagged as a widget schema extender once the theme is installed', function (): void {
    bootEditorialSectionVariantLifecycle();

    $extenders = collect(app()->tagged(WidgetSchemaExtender::TAG));

    expect($extenders->contains(fn (object $extender): bool => $extender instanceof SectionVariantSchemaExtender))
        ->toBeTrue();
});

it('appends a variant select bound to the meta state path', function (): void {
    $components = (new SectionVariantSchemaExtender)->extendDisplayComponents(Schema::make(), ['existing']);

    expect($components)->toHaveCount(2)
        ->and($components[0])->toBe('existing')
        ->and($components[1])->toBeInstanceOf(Select::class);

    $select = $components[1] ?? null;
    throw_unless($select instanceof Select, RuntimeException::class, 'Expected the section variant extender to append a Select component.');

    expect($select->getName())->toBe('variant');
});

it('offers the active theme declared variants for the edited section type', function (): void {
    bootEditorialSectionVariantLifecycle();
    activateEditorialTheme();

    expect(SectionVariantSchemaExtender::optionsFor('breaking-news-ribbon'))
        ->toBe(['default' => 'Default', 'compact' => 'Compact']);
});

it('offers nothing for a section type the active theme does not declare', function (): void {
    bootEditorialSectionVariantLifecycle();
    activateEditorialTheme();

    expect(SectionVariantSchemaExtender::optionsFor('hero'))->toBe([])
        ->and(SectionVariantSchemaExtender::optionsFor(null))->toBe([])
        ->and(SectionVariantSchemaExtender::optionsFor(''))->toBe([]);
});

it('offers nothing when the active theme declares no section variants', function (): void {
    bootEditorialSectionVariantLifecycle();
    Theme::query()->update(['default' => false]);

    expect(SectionVariantSchemaExtender::optionsFor('breaking-news-ribbon'))->toBe([]);
});

it('persists the selected variant under the widget meta state path', function (): void {
    bootEditorialSectionVariantLifecycle();
    activateEditorialTheme();

    $this->app->register(CuratorServiceProvider::class);
    resolve(LayoutBuilderAdminRegistrar::class)->register();

    test()->actingAsAdmin();

    $permission = Permission::findOrCreate(
        CapellPermission::ManageAdvancedPresentationSettings->name(),
        'web',
    );
    auth()->user()?->givePermissionTo($permission);

    $type = Blueprint::factory()->create([
        'type' => 'widget',
        'admin' => [
            'type_configurator' => 'Widget',
            'configurator' => 'Default',
        ],
    ]);
    $widget = Widget::factory()->create([
        'blueprint_id' => $type->getKey(),
        'meta' => [
            'type' => 'breaking-news-ribbon',
        ],
    ]);

    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->assertFormFieldVisible('meta.variant')
        ->assertFormFieldExists('meta.variant', fn (Select $field): bool => $field->getOptions() === [
            'default' => 'Default',
            'compact' => 'Compact',
        ])
        ->fillForm([
            'meta' => [
                'variant' => 'compact',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($widget->refresh()->meta)->toMatchArray([
        'type' => 'breaking-news-ribbon',
        'variant' => 'compact',
    ]);

    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->fillForm(['meta' => ['variant' => null]])
        ->call('save')->assertHasNoFormErrors();
    expect($widget->refresh()->meta['variant'] ?? null)->toBe('default');
    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->assertFormSet(['meta.variant' => 'default']);
    expect(SectionVariantViewResolver::resolve('capell-theme-editorial', $widget->meta ?? []))
        ->toBe('breaking-news-ribbon');

});

it('uses the effective target theme through the real editor and renders the saved variant on its public route', function (bool $layoutOverride): void {
    require_once dirname(__DIR__, 4) . '/tests/Packages/Support/ThemeLayoutNativeSupport.php';
    [$url] = layoutNativeThemeCreatePage('editorial', 'Variant context');
    bootEditorialSectionVariantLifecycle();
    $page = Page::query()->where('meta->theme_demo->theme_key', 'editorial')->where('meta->theme_demo->surface', 'homepage')->firstOrFail();
    $page->loadMissing(['layout.theme', 'site']);

    $globalDefinition = clone EditorialThemeServiceProvider::definition();
    $globalDefinition->key = 'global-variant-context';
    $globalDefinition->frontend['sectionVariants'] = ['breaking-news-ribbon' => ['default', 'wide']];
    $siteDefinition = clone EditorialThemeServiceProvider::definition();
    $siteDefinition->key = 'site-variant-context';
    $siteDefinition->frontend['sectionVariants'] = ['breaking-news-ribbon' => ['default', 'stacked']];
    resolve(ThemeRegistry::class)->register($globalDefinition);
    resolve(ThemeRegistry::class)->register($siteDefinition);

    $global = Theme::factory()->create(['key' => 'global-variant-context', 'default' => true]);
    Theme::query()->whereKeyNot($global->getKey())->update(['default' => false]);
    $siteTheme = Theme::factory()->create(['key' => $siteDefinition->key, 'default' => false]);
    $site = $page->site ?? throw new LogicException('Expected the demo page site.');
    $layout = $page->layout ?? throw new LogicException('Expected the demo page layout.');
    $site->forceFill(['theme_id' => $layoutOverride ? $siteTheme->getKey() : Theme::query()->where('key', 'editorial')->sole()->getKey()])->save();
    $layout->forceFill(['theme_id' => $layoutOverride ? Theme::query()->where('key', 'editorial')->sole()->getKey() : null])->save();
    $widget = Widget::query()->where('meta->type', 'breaking-news-ribbon')->firstOrFail();
    $this->app->register(CuratorServiceProvider::class);
    resolve(LayoutBuilderAdminRegistrar::class)->register();
    test()->actingAsAdmin();
    auth()->user()?->givePermissionTo(Permission::findOrCreate(CapellPermission::ManageAdvancedPresentationSettings->name(), 'web'));
    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->assertFormFieldVisible('meta.variant')
        ->assertFormFieldExists('meta.variant', fn (Select $field): bool => array_keys($field->getOptions()) === ['default', 'compact'])
        ->fillForm(['meta' => ['variant' => 'compact']])->call('save')->assertHasNoFormErrors();
    expect($widget->refresh()->meta['variant'] ?? null)->toBe('compact');
    auth()->logout();
    CapellLayoutManager::clearContainerWidgets();
    get($url->full_url)->assertOk()->assertSee('dnews-ribbon-compact', false)
        ->assertDontSee('meta.variant')->assertDontSee('data-capell-editor');
})->with(['layout override' => true, 'site fallback' => false]);

it('offers the safe intersection for a shared widget and explains stale saved variants', function (bool $declared): void {
    bootEditorialSectionVariantLifecycle();
    activateEditorialTheme();
    $this->app->register(CuratorServiceProvider::class);
    resolve(LayoutBuilderAdminRegistrar::class)->register();
    test()->actingAsAdmin();
    auth()->user()?->givePermissionTo(Permission::findOrCreate(CapellPermission::ManageAdvancedPresentationSettings->name(), 'web'));
    $widget = Widget::factory()->create([
        'blueprint_id' => Blueprint::factory()->create(['type' => 'widget', 'admin' => ['type_configurator' => 'Widget', 'configurator' => 'Default']])->getKey(),
        'meta' => ['type' => 'breaking-news-ribbon', 'variant' => 'stale-variant'],
    ]);
    $editorial = Theme::query()->where('key', 'editorial')->sole();
    $other = Theme::factory()->create(['key' => 'default', 'default' => false]);
    foreach ($declared ? [$editorial, $other] : [$other] as $theme) {
        Layout::factory()->create([
            'theme_id' => $theme->getKey(),
            'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]],
        ]);
    }

    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->assertFormFieldVisible('meta.variant')
        ->assertFormFieldExists('meta.variant', fn (Select $field): bool => array_keys($field->getOptions()) === ['default'])
        ->assertSee(__('capell-theme-foundation::form.section_variant_shared'))
        ->assertSee(__('capell-theme-foundation::form.section_variant_stale'))
        ->fillForm(['name' => 'Shared widget with preserved variant'])->call('save')->assertHasNoFormErrors();
    expect($widget->refresh()->meta['variant'] ?? null)->toBe('stale-variant');
    expect(SectionVariantViewResolver::resolve('capell-theme-editorial', $widget->meta ?? []))->toBe('breaking-news-ribbon');
    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->fillForm(['meta' => ['variant' => 'new-unsupported-variant']])
        ->call('save')->assertHasFormErrors(['meta.variant' => 'in']);
    expect($widget->refresh()->meta['variant'] ?? null)->toBe('stale-variant');
    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->fillForm(['meta' => ['variant' => null]])->call('save')->assertHasNoFormErrors();
    expect($widget->refresh()->meta['variant'] ?? null)->toBe('default');
    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->fillForm(['meta' => ['variant' => 'default']])->call('save')->assertHasNoFormErrors();
    expect($widget->refresh()->meta['variant'] ?? null)->toBe('default');
})->with(['declared intersection' => true, 'no declarations' => false]);

it('keeps the picker hidden for tracked widgets that are not declared sections', function (?string $sectionType): void {
    bootEditorialSectionVariantLifecycle();
    activateEditorialTheme();
    $this->app->register(CuratorServiceProvider::class);
    resolve(LayoutBuilderAdminRegistrar::class)->register();
    test()->actingAsAdmin();
    auth()->user()?->givePermissionTo(Permission::findOrCreate(CapellPermission::ManageAdvancedPresentationSettings->name(), 'web'));
    $widget = Widget::factory()->create([
        'blueprint_id' => Blueprint::factory()->create(['type' => 'widget', 'admin' => ['type_configurator' => 'Widget', 'configurator' => 'Default']])->getKey(),
        'meta' => $sectionType === null ? [] : ['type' => $sectionType],
    ]);
    Layout::factory()->create([
        'theme_id' => Theme::query()->where('key', 'editorial')->sole()->getKey(),
        'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]],
    ]);
    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])->assertFormFieldHidden('meta.variant');
})->with(['no section metadata' => null, 'ordinary widget type' => 'page-content']);

it('intersects every registered pageable consumer in the editor and public rendering', function (bool $event, bool $pageAsset): void {
    $consumerClass = $event ? Event::class : Article::class;
    bootEditorialSectionVariantLifecycle();
    $editorial = activateEditorialTheme();
    // Register through Core's public extension point; production must not depend on these packages.
    CapellCore::registerPageType(new PageTypeData('variant-consumer', $consumerClass));
    Relation::morphMap(['event' => Event::class]);
    $this->artisan('migrate', [
        '--path' => dirname(__DIR__, 3) . '/events/database/migrations',
        '--realpath' => true,
    ])->assertSuccessful();

    $otherDefinition = clone EditorialThemeServiceProvider::definition();
    $otherDefinition->key = 'other-variant-consumer';
    $otherDefinition->frontend['sectionVariants'] = ['breaking-news-ribbon' => ['default', 'stacked']];
    resolve(ThemeRegistry::class)->register($otherDefinition);
    $other = Theme::factory()->create(['key' => $otherDefinition->key, 'default' => false]);
    $widget = Widget::factory()->create([
        'blueprint_id' => Blueprint::factory()->create(['type' => 'widget', 'admin' => ['type_configurator' => 'Widget', 'configurator' => 'Default']])->getKey(),
        'meta' => ['type' => 'breaking-news-ribbon', 'variant' => 'compact', 'state' => 'active', 'heading' => 'Shared breaking news'],
    ]);
    // The layout's own site is deliberately different; consumers determine the frontend fallback.
    $layout = Layout::factory()->create([
        'theme_id' => null,
        'containers' => $pageAsset ? [] : ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]],
    ]);
    $page = Page::factory()->create(['layout_id' => $layout->getKey(), 'site_id' => Site::factory()->create(['theme_id' => $editorial->getKey()])->getKey()]);
    $consumer = $consumerClass::factory()->create([
        'layout_id' => $layout->getKey(),
        'site_id' => Site::factory()->create(['theme_id' => $other->getKey()])->getKey(),
    ]);
    if ($pageAsset) {
        foreach ([$page, $consumer] as $owner) {
            $widget->assets()->create(['pageable_type' => $owner->getMorphClass(), 'pageable_id' => $owner->getKey(), 'asset_type' => $widget->getMorphClass(), 'asset_id' => $widget->getKey(), 'container' => 'main']);
        }
    }

    $hydratedConsumers = 0;
    $events = Page::getEventDispatcher() ?? throw new LogicException('Expected the model event dispatcher.');
    $isolatedEvents = clone $events;
    Page::setEventDispatcher($isolatedEvents);
    $listener = static function () use (&$hydratedConsumers): void {
        $hydratedConsumers++;
    };
    // Listen only around scope resolution: public rendering below may load its page normally.
    foreach ([Page::class, $consumerClass] as $class) {
        $isolatedEvents->listen('eloquent.retrieved: ' . $class, $listener);
    }
    try {
        $scope = ResolveSectionVariantScopeAction::run($widget);
        expect($scope->hasTrackedUses)->toBeTrue()
            ->and($scope->themeKeys)->toEqualCanonicalizing(['editorial', $other->key])
            ->and(array_keys(SectionVariantSchemaExtender::optionsFor('breaking-news-ribbon', $scope->themeKeys)))->toBe(['default'])
            ->and($hydratedConsumers)->toBe(0);
    } finally {
        Page::setEventDispatcher($events);
    }

    $this->app->register(CuratorServiceProvider::class);
    resolve(LayoutBuilderAdminRegistrar::class)->register();
    test()->actingAsAdmin();
    auth()->user()?->givePermissionTo(Permission::findOrCreate(CapellPermission::ManageAdvancedPresentationSettings->name(), 'web'));
    Livewire::test(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->assertFormFieldVisible('meta.variant')
        ->assertFormFieldExists('meta.variant', fn (Select $field): bool => array_keys($field->getOptions()) === ['default'])
        ->fillForm(['meta' => ['variant' => 'default']])->call('save')->assertHasNoFormErrors();
    expect($widget->refresh()->meta['variant'] ?? null)->toBe('default');
    auth()->logout();

    foreach ([$page, $consumer] as $owner) {
        $work = new FrontendWork(Request::create('/'), new FrontendState(site: $owner->site, page: $owner));
        resolve(ThemeResolverStep::class)->handle($work, static fn (FrontendWork $work): FrontendWork => $work);
        expect($work->state->theme()?->key)->toBe($owner->site->theme->key);
        // Both fixture definitions use Editorial's public section views.
        $namespace = 'capell-theme-editorial';
        $view = SectionVariantViewResolver::resolve($namespace, $widget->meta ?? []);
        expect($view)->toBe('breaking-news-ribbon');
        $viewName = $namespace . '::sections.' . $view;
        if (! view()->exists($viewName)) {
            throw new LogicException('Expected the resolved public section view to exist.');
        }

        $html = view($viewName, ['section' => $widget->meta])->render();
        expect($html)->toContain('Shared breaking news')->not->toContain('dnews-ribbon-compact', 'meta.variant', 'data-capell-editor');
    }
})->with(['article' => false, 'event' => true])->with(['layout reference' => false, 'page asset' => true]);
