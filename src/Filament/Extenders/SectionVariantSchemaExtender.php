<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Filament\Extenders;

use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetSchemaExtender;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Str;
use Throwable;

/**
 * Adds a per-section "Variant" picker to the widget appearance tab.
 *
 * The appearance grid is state-pathed to `meta`, so the field writes straight
 * into `meta['variant']` — the same key each theme's `widget.section` Blade
 * reads through `SectionVariantViewResolver`. Options come from the active
 * theme's own `frontend['sectionVariants']` declaration, keyed by the section
 * type held in the sibling `meta['type']`.
 */
final class SectionVariantSchemaExtender implements WidgetSchemaExtender
{
    /**
     * Options for one section type, labelled from the raw variant key.
     *
     * @return array<string, string>
     */
    public static function optionsFor(?string $sectionType): array
    {
        if ($sectionType === null || $sectionType === '') {
            return [];
        }

        $variants = self::activeThemeSectionVariants()[$sectionType] ?? null;

        if (! is_array($variants)) {
            return [];
        }

        $options = [];

        foreach ($variants as $variant) {
            if (! is_string($variant) || $variant === '') {
                continue;
            }

            $options[$variant] = $variant === 'default'
                ? __('capell-admin::generic.default')
                : Str::headline($variant);
        }

        return $options;
    }

    /**
     * @param  array<int, mixed>  $components
     * @return array<int, mixed>
     */
    public function extendDisplayComponents(Schema $schema, array $components): array
    {
        $components[] = Select::make('variant')
            ->label(__('capell-theme-foundation::form.section_variant'))
            ->helperText(__('capell-theme-foundation::form.section_variant_helper'))
            ->native(false)
            ->options(fn (Get $get): array => self::optionsFor(self::sectionType($get, $schema)))
            ->visible(fn (Get $get): bool => count(self::optionsFor(self::sectionType($get, $schema))) > 1);

        return $components;
    }

    /**
     * The active theme's declared section variants, or an empty map.
     *
     * @return array<string, mixed>
     */
    private static function activeThemeSectionVariants(): array
    {
        $definition = self::activeThemeDefinition();

        if (! $definition instanceof ThemeDefinitionData) {
            return [];
        }

        $variants = $definition->frontend['sectionVariants'] ?? null;

        return is_array($variants) ? $variants : [];
    }

    private static function activeThemeDefinition(): ?ThemeDefinitionData
    {
        try {
            $key = Theme::query()->where('default', true)->value('key');
        } catch (Throwable) {
            return null;
        }

        if (! is_string($key) || $key === '' || ! app()->bound(ThemeRegistry::class)) {
            return null;
        }

        $definition = resolve(ThemeRegistry::class)->definitions()[$key] ?? null;

        return $definition instanceof ThemeDefinitionData ? $definition : null;
    }

    private static function sectionType(Get $get, Schema $schema): ?string
    {
        $type = $get('type');

        if (is_string($type) && $type !== '') {
            return $type;
        }

        $record = $schema->getRecord();

        if (! $record instanceof EloquentModel) {
            return null;
        }

        $meta = $record->getAttribute('meta');
        $type = is_array($meta) ? ($meta['type'] ?? null) : null;

        return is_string($type) && $type !== '' ? $type : null;
    }
}
