<?php

declare(strict_types=1);

use Awcodes\Curator\CuratorServiceProvider;
use Capell\Admin\Enums\CapellPermission;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Filament\Extenders\SectionVariantSchemaExtender;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\ThemeEditorial\EditorialThemeServiceProvider;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

function bootEditorialSectionVariantLifecycle(): void
{
    CapellCore::forcePackageInstalled(FoundationThemeServiceProvider::$packageName);
    CapellCore::forcePackageInstalled(EditorialThemeServiceProvider::$packageName);

    $foundationProvider = new FoundationThemeServiceProvider(app());
    $foundationProvider->register();
    $foundationProvider->boot();

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
});
