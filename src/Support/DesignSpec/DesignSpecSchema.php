<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\DesignSpec;

use InvalidArgumentException;

final class DesignSpecSchema
{
    private function __construct() {}

    /** @return array<string, mixed> */
    public static function toArray(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => 'https://capell.app/schemas/design-spec/v1.json',
            'title' => 'Capell DesignSpec v1',
            'type' => 'object',
            'additionalProperties' => false,
            'required' => self::keys('root'),
            'properties' => [
                'schemaVersion' => ['const' => DesignSpecConstraints::SCHEMA_VERSION],
                'template' => ['const' => DesignSpecConstraints::TEMPLATE],
                'display' => ['$ref' => '#/$defs/display'],
                'sites' => ['type' => 'array', 'minItems' => 1, 'maxItems' => DesignSpecConstraints::MAX_SITES, 'items' => ['$ref' => '#/$defs/site']],
                'locales' => ['type' => 'array', 'minItems' => 1, 'maxItems' => DesignSpecConstraints::MAX_LOCALES, 'items' => ['$ref' => '#/$defs/locale']],
                'brand' => ['$ref' => '#/$defs/brand'],
                'palette' => ['$ref' => '#/$defs/palette'],
                'typography' => ['$ref' => '#/$defs/typography'],
                'layout' => ['$ref' => '#/$defs/layout'],
                'components' => ['$ref' => '#/$defs/components'],
                'accessibility' => ['$ref' => '#/$defs/accessibility'],
                'assets' => ['type' => 'array', 'maxItems' => DesignSpecConstraints::MAX_ASSET_FILES, 'uniqueItems' => true, 'items' => ['$ref' => '#/$defs/asset']],
            ],
            '$defs' => self::definitions(),
        ];
    }

    /** @return list<string> */
    public static function keys(string $object): array
    {
        return match ($object) {
            'root' => ['schemaVersion', 'template', 'display', 'sites', 'locales', 'brand', 'palette', 'typography', 'layout', 'components', 'accessibility', 'assets'],
            'display' => ['name', 'description'],
            'site' => ['key', 'name', 'localeCodes', 'defaultLocale', 'fallbackLocaleCodes'],
            'locale' => ['code', 'label', 'direction'],
            'brand' => ['name', 'logoAssetId'],
            'palette' => ['light', 'dark'],
            'colorMode' => ['background', 'surface', 'text', 'mutedText', 'largeText', 'primary', 'onPrimary', 'border', 'focus'],
            'typography' => ['locales'],
            'localeTypography' => ['locale', 'heading', 'body'],
            'typographyRole' => ['family', 'style', 'weight', 'fontAssetId'],
            'layout' => array_keys(DesignSpecConstraints::LAYOUT_OPTIONS),
            'components' => array_keys(DesignSpecConstraints::COMPONENT_VARIANTS),
            'accessibility' => ['reducedMotion', 'focusIndicator', 'landmarks', 'headingHierarchy'],
            'asset' => ['id'],
            default => throw new InvalidArgumentException('Unknown DesignSpec schema object.'),
        };
    }

    /** @return list<string|int> */
    public static function enum(string $object, string $field): array
    {
        $property = self::property($object, $field);
        $enum = $property['enum'] ?? null;
        if (is_array($enum) && array_is_list($enum)) {
            $values = [];
            foreach ($enum as $value) {
                if (! is_string($value) && ! is_int($value)) {
                    throw new InvalidArgumentException('Invalid DesignSpec schema enum.');
                }

                $values[] = $value;
            }

            return $values;
        }

        $constant = $property['const'] ?? null;
        if (is_string($constant) || is_int($constant)) {
            return [$constant];
        }

        return [];
    }

    /** @return array<string, mixed> */
    public static function property(string $object, string $field): array
    {
        $schema = $object === 'root' ? self::toArray() : self::definitions()[$object] ?? null;
        $properties = is_array($schema) ? $schema['properties'] ?? null : null;
        if (! is_array($properties)) {
            throw new InvalidArgumentException('Unknown DesignSpec schema field.');
        }

        $property = $properties[$field] ?? null;
        if (! is_array($property)) {
            throw new InvalidArgumentException('Unknown DesignSpec schema field.');
        }

        $normalized = [];
        foreach ($property as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Unknown DesignSpec schema field.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /** @return array<string, array<string, mixed>> */
    private static function definitions(): array
    {
        $closed = static fn (string $name, array $properties, ?int $maximumProperties = null): array => array_filter([
            'type' => 'object',
            'additionalProperties' => false,
            'required' => self::keys($name),
            'properties' => $properties,
            'maxProperties' => $maximumProperties,
        ], static fn (mixed $value): bool => $value !== null);
        $color = ['type' => 'string', 'pattern' => '^#[0-9A-F]{6}$'];
        $assetId = ['type' => ['string', 'null'], 'pattern' => '^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$', 'maxLength' => DesignSpecConstraints::MAX_IDENTIFIER_LENGTH];

        return [
            'display' => $closed('display', [
                'name' => self::text('display.name'),
                'description' => self::text('display.description'),
            ]),
            'site' => $closed('site', [
                'key' => ['type' => 'string', 'pattern' => '^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$', 'maxLength' => DesignSpecConstraints::MAX_IDENTIFIER_LENGTH],
                'name' => self::text('site.name'),
                'localeCodes' => ['type' => 'array', 'minItems' => 1, 'maxItems' => DesignSpecConstraints::MAX_LOCALES, 'uniqueItems' => true, 'items' => ['enum' => DesignSpecConstraints::SUPPORTED_LOCALES]],
                'defaultLocale' => ['enum' => DesignSpecConstraints::SUPPORTED_LOCALES],
                'fallbackLocaleCodes' => ['type' => 'array', 'minItems' => 1, 'maxItems' => DesignSpecConstraints::MAX_LOCALES, 'uniqueItems' => true, 'items' => ['enum' => DesignSpecConstraints::SUPPORTED_LOCALES]],
            ]),
            'locale' => $closed('locale', [
                'code' => ['enum' => DesignSpecConstraints::SUPPORTED_LOCALES],
                'label' => self::text('locale.label'),
                'direction' => ['enum' => ['ltr', 'rtl']],
            ]),
            'brand' => $closed('brand', [
                'name' => self::text('brand.name'),
                'logoAssetId' => $assetId,
            ]),
            'palette' => $closed('palette', ['light' => ['$ref' => '#/$defs/colorMode'], 'dark' => ['$ref' => '#/$defs/colorMode']]),
            'colorMode' => $closed('colorMode', array_fill_keys(self::keys('colorMode'), $color), DesignSpecConstraints::MAX_PALETTE_COLORS),
            'typography' => $closed('typography', [
                'locales' => ['type' => 'array', 'minItems' => 1, 'maxItems' => DesignSpecConstraints::MAX_LOCALES, 'items' => ['$ref' => '#/$defs/localeTypography']],
            ]),
            'localeTypography' => $closed('localeTypography', [
                'locale' => ['enum' => DesignSpecConstraints::SUPPORTED_LOCALES],
                'heading' => ['$ref' => '#/$defs/typographyRole'],
                'body' => ['$ref' => '#/$defs/typographyRole'],
            ]),
            'typographyRole' => $closed('typographyRole', [
                'family' => ['enum' => DesignSpecConstraints::FONT_FAMILIES],
                'style' => ['enum' => DesignSpecConstraints::FONT_STYLES],
                'weight' => ['enum' => DesignSpecConstraints::FONT_WEIGHTS],
                'fontAssetId' => $assetId,
            ]),
            'layout' => $closed('layout', self::enumProperties(DesignSpecConstraints::LAYOUT_OPTIONS)),
            'components' => $closed('components', self::enumProperties(DesignSpecConstraints::COMPONENT_VARIANTS), DesignSpecConstraints::MAX_COMPONENT_SELECTIONS),
            'accessibility' => $closed('accessibility', [
                'reducedMotion' => ['const' => 'respect'],
                'focusIndicator' => ['const' => 'visible'],
                'landmarks' => ['const' => 'semantic'],
                'headingHierarchy' => ['const' => 'ordered'],
            ]),
            'asset' => $closed('asset', ['id' => ['enum' => array_keys(DesignSpecConstraints::ASSET_CATALOGUE)]]),
        ];
    }

    /** @return array{type: string, minLength: int, maxLength: int, pattern: string} */
    private static function text(string $field): array
    {
        $constraint = DesignSpecConstraints::TEXT_FIELDS[$field];

        return [
            'type' => 'string',
            'minLength' => $constraint['minimum'],
            'maxLength' => $constraint['maximum'],
            'pattern' => DesignSpecConstraints::TEXT_PATTERN,
        ];
    }

    /**
     * @param  array<string, list<string>>  $options
     * @return array<string, array{enum: list<string>}>
     */
    private static function enumProperties(array $options): array
    {
        $properties = [];
        foreach ($options as $field => $variants) {
            $properties[$field] = ['enum' => $variants];
        }

        return $properties;
    }
}
