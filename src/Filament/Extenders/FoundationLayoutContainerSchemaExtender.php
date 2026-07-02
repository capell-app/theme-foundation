<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Filament\Extenders;

use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;
use Capell\LayoutBuilder\Contracts\Extenders\LayoutContainerSchemaExtender;
use Capell\LayoutBuilder\Data\LayoutContainerSchemaContextData;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

final class FoundationLayoutContainerSchemaExtender implements LayoutContainerSchemaExtender
{
    public function themeKey(): string
    {
        return FoundationThemeServiceProvider::THEME_KEY;
    }

    public function themeLabel(): string
    {
        return __('capell-theme-foundation::generic.theme_foundation');
    }

    public function supports(LayoutContainerSchemaContextData $context): bool
    {
        return $context->themeKey === $this->themeKey();
    }

    /**
     * @return array<int, mixed>
     */
    public function extendContainerComponents(Schema $schema, LayoutContainerSchemaContextData $context): array
    {
        return [
            Select::make('surface_tone')
                ->label(__('capell-theme-foundation::form.container_surface_tone'))
                ->options([
                    'default' => __('capell-theme-foundation::form.container_surface_tone_options.default'),
                    'muted' => __('capell-theme-foundation::form.container_surface_tone_options.muted'),
                    'contrast' => __('capell-theme-foundation::form.container_surface_tone_options.contrast'),
                ])
                ->default('default')
                ->native(false),
        ];
    }
}
