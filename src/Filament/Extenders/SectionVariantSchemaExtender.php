<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Filament\Extenders;

use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Actions\ResolveSectionVariantScopeAction;
use Capell\FoundationTheme\Data\SectionVariantScopeData;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetSchemaExtender;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Str;
use Override;
use Throwable;

/**
 * Adds a per-section "Variant" picker to the widget appearance tab.
 *
 * The appearance grid is state-pathed to `meta`, so the field writes straight
 * into `meta['variant']` — the same key each theme's `widget.section` Blade
 * reads through `SectionVariantViewResolver`. Options are the intersection of the effective
 * page/layout themes' own `frontend['sectionVariants']` declaration, keyed by the section
 * type held in the sibling `meta['type']`.
 */
final class SectionVariantSchemaExtender implements WidgetSchemaExtender
{
    /**
     * Options for one section type, labelled from the raw variant key.
     *
     * @param  list<string>|null  $themeKeys
     * @return array<string, string>
     */
    public static function optionsFor(?string $sectionType, ?array $themeKeys = null): array
    {
        if ($sectionType === null || $sectionType === '') {
            return [];
        }

        if ($themeKeys !== null) {
            $intersection = null;
            $hasDeclarations = false;
            foreach ($themeKeys as $key) {
                $definition = resolve(ThemeRegistry::class)->definitions()[$key] ?? null;
                $declarations = $definition instanceof ThemeDefinitionData ? ($definition->frontend['sectionVariants'] ?? []) : [];
                $declared = is_array($declarations) ? ($declarations[$sectionType] ?? []) : [];
                $declared = is_array($declared) ? array_values(array_filter($declared, is_string(...))) : [];
                $hasDeclarations = $hasDeclarations || $declared !== [];
                $declared = array_values(array_unique(['default', ...$declared]));
                $intersection = $intersection === null ? $declared : array_values(array_intersect($intersection, $declared));
            }

            $variants = $hasDeclarations ? ($intersection ?? ['default']) : [];
        } else {
            $variants = self::activeThemeSectionVariants()[$sectionType] ?? null;
        }

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
    #[Override]
    public function extendDisplayComponents(Schema $schema, array $components): array
    {
        $scope = null;
        $resolveScope = static function () use ($schema, &$scope): SectionVariantScopeData {
            $record = $schema->getRecord();

            return $scope ??= ResolveSectionVariantScopeAction::run($record instanceof Widget ? $record : null);
        };
        $options = static function (Get $get) use ($schema, $resolveScope): array {
            $context = $resolveScope();

            $available = self::optionsFor(self::sectionType($get, $schema), $context->hasTrackedUses ? $context->themeKeys : null);
            $variant = $get('variant');
            if ($variant === null || $variant === '') {
                $variant = self::savedVariant($schema);
            }

            return $available === [] && $context->hasTrackedUses && is_string($variant) && $variant !== ''
                ? ['default' => __('capell-admin::generic.default')]
                : $available;
        };
        $components[] = Select::make('variant')
            ->label(__('capell-theme-foundation::form.section_variant'))
            ->helperText(static function (Get $get) use ($resolveScope, $options): string {
                $context = $resolveScope();
                $message = __('capell-theme-foundation::form.' . ($context->hasTrackedUses ? 'section_variant_shared' : 'section_variant_unplaced'));
                $variant = $get('variant');
                if (is_string($variant) && $variant !== '' && ! array_key_exists($variant, $options($get))) {
                    $message .= ' ' . __('capell-theme-foundation::form.section_variant_stale');
                }

                return $message;
            })
            ->native(false)
            ->options($options)
            ->in(static function (Get $get) use ($schema, $options): array {
                $allowed = array_keys($options($get));
                $saved = self::savedVariant($schema);
                // A legacy value can survive unrelated edits, but new unsupported values cannot be saved.
                if (is_string($saved) && $saved !== '') {
                    $allowed[] = $saved;
                }

                return array_values(array_unique($allowed));
            })
            ->visible(fn (Get $get): bool => self::sectionType($get, $schema) !== null && ($resolveScope()->hasTrackedUses ? $options($get) !== [] : count($options($get)) > 1))
            // Clearing is an explicit reset. Omitted unsupported strings stay
            // out of the payload so unrelated edits preserve legacy metadata.
            ->dehydrateStateUsing(static fn (?string $state): string => $state === null || $state === '' ? 'default' : $state)
            ->dehydrated(fn (Get $get): bool => $get('variant') === null || $get('variant') === '' || (is_string($get('variant')) && array_key_exists($get('variant'), $options($get))));

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

    private static function savedVariant(Schema $schema): ?string
    {
        $record = $schema->getRecord();
        $meta = $record instanceof EloquentModel ? $record->getAttribute('meta') : null;
        $variant = is_array($meta) ? ($meta['variant'] ?? null) : null;

        return is_string($variant) && $variant !== '' ? $variant : null;
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
