<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Theme;
use Capell\FoundationTheme\Filament\Extenders\FoundationLayoutContainerSchemaExtender;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\Data\LayoutContainerSchemaContextData;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use RuntimeException;

it('adds foundation theme layout container fields for the foundation theme key', function (): void {
    $theme = Theme::factory()->create(['key' => FoundationThemeServiceProvider::THEME_KEY]);
    $layout = Layout::factory()->for($theme)->create();
    $context = LayoutContainerSchemaContextData::fromLayout($layout, 'main');
    $extender = new FoundationLayoutContainerSchemaExtender;

    $components = $extender->extendContainerComponents(Schema::make()->record($layout), $context);

    $firstComponent = $components[0] ?? null;
    if (! $firstComponent instanceof Select) {
        throw new RuntimeException('Expected first component to be a Select');
    }

    expect($extender->supports($context))->toBeTrue()
        ->and($extender->themeKey())->toBe(FoundationThemeServiceProvider::THEME_KEY)
        ->and($components)->toHaveCount(1)
        ->and($firstComponent)->toBeInstanceOf(Select::class)
        ->and($firstComponent->getName())->toBe('surface_tone');
});
