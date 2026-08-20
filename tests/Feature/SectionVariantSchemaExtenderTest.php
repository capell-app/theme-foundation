<?php

declare(strict_types=1);

use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Filament\Extenders\SectionVariantSchemaExtender;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetSchemaExtender;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

/**
 * @param  array<string, array<int, string>>  $sectionVariants
 */
function registerActiveVariantTheme(array $sectionVariants): void
{
    $registry = resolve(ThemeRegistry::class);

    $registry->register(new ThemeDefinitionData(
        key: 'variant-fixture',
        name: 'Variant Fixture',
        description: 'Fixture theme declaring section variants.',
        package: 'capell-app/theme-foundation',
        previewImage: '',
        tags: [],
        bestFit: [],
        presets: [],
        runtime: FrontendRuntime::Blade,
        frontend: ['sectionVariants' => $sectionVariants],
    ));

    Theme::query()->update(['default' => false]);

    Theme::query()->updateOrCreate(
        ['key' => 'variant-fixture'],
        [
            'name' => 'Variant Fixture',
            'blueprint_id' => Blueprint::factory()->theme()->create()->getKey(),
            'default' => true,
            'status' => true,
        ],
    );
}

it('is tagged as a widget schema extender once the theme is installed', function (): void {
    $provider = app()->getProvider(FoundationThemeServiceProvider::class);

    expect($provider)->toBeInstanceOf(FoundationThemeServiceProvider::class);

    $register = new ReflectionMethod($provider, 'registerWidgetSchemaExtenders');
    $register->invoke($provider);

    $extenders = collect(app()->tagged(WidgetSchemaExtender::TAG));

    expect($extenders->contains(fn (object $extender): bool => $extender instanceof SectionVariantSchemaExtender))
        ->toBeTrue();
});

it('appends a variant select bound to the meta state path', function (): void {
    $components = (new SectionVariantSchemaExtender)->extendDisplayComponents(Schema::make(), ['existing']);

    expect($components)->toHaveCount(2)
        ->and($components[0])->toBe('existing')
        ->and($components[1])->toBeInstanceOf(Select::class)
        ->and($components[1]->getName())->toBe('variant');
});

it('offers the active theme declared variants for the edited section type', function (): void {
    registerActiveVariantTheme([
        'breaking-news-ribbon' => ['default', 'compact'],
    ]);

    expect(SectionVariantSchemaExtender::optionsFor('breaking-news-ribbon'))
        ->toBe(['default' => 'Default', 'compact' => 'Compact']);
});

it('offers nothing for a section type the active theme does not declare', function (): void {
    registerActiveVariantTheme([
        'breaking-news-ribbon' => ['default', 'compact'],
    ]);

    expect(SectionVariantSchemaExtender::optionsFor('hero'))->toBe([])
        ->and(SectionVariantSchemaExtender::optionsFor(null))->toBe([])
        ->and(SectionVariantSchemaExtender::optionsFor(''))->toBe([]);
});

it('offers nothing when the active theme declares no section variants', function (): void {
    registerActiveVariantTheme([]);

    expect(SectionVariantSchemaExtender::optionsFor('breaking-news-ribbon'))->toBe([]);
});
