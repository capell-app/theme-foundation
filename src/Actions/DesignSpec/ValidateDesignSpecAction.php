<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions\DesignSpec;

use Capell\FoundationTheme\Data\DesignSpec\DesignSpecAccessibilityData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecAssetData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecBrandData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecColorModeData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecComponentsData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecDisplayData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecLayoutData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecLocaleData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecLocaleTypographyData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecPaletteData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecSiteData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecTypographyData;
use Capell\FoundationTheme\Data\DesignSpec\DesignSpecTypographyRoleData;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecConstraints;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecSchema;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static DesignSpecData run(array<string, mixed>|string $specification) */
final class ValidateDesignSpecAction
{
    use AsFake;
    use AsObject;

    /** @var array<string, array{kind: 'font'|'image', bytes: int}> */
    private array $assetCatalogue = DesignSpecConstraints::ASSET_CATALOGUE;

    /** @param array<string, mixed>|string $specification */
    public function handle(array|string $specification): DesignSpecData
    {
        $payload = $this->payload($specification);
        $this->assertNestingDepth($payload);
        $root = $this->object($payload, '$', 'root');
        $this->rejectForbiddenKeys($root);
        $this->constant($root['schemaVersion'], '$.schemaVersion', DesignSpecConstraints::SCHEMA_VERSION);
        $this->constant($root['template'], '$.template', DesignSpecConstraints::TEMPLATE);

        $locales = $this->locales($root['locales']);
        $localeCodes = array_map(static fn (DesignSpecLocaleData $locale): string => $locale->code, $locales);
        $sites = $this->sites($root['sites'], $localeCodes);
        $assets = $this->assets($root['assets']);
        $assetsById = [];
        foreach ($assets as $asset) {
            $assetsById[$asset->id] = $asset;
        }

        return new DesignSpecData(
            schemaVersion: DesignSpecConstraints::SCHEMA_VERSION,
            template: DesignSpecConstraints::TEMPLATE,
            display: $this->display($root['display']),
            sites: $sites,
            locales: $locales,
            brand: $this->brand($root['brand'], $assetsById),
            palette: $this->palette($root['palette']),
            typography: $this->typography($root['typography'], $localeCodes, $assetsById),
            layout: $this->layout($root['layout']),
            components: $this->components($root['components']),
            accessibility: $this->accessibility($root['accessibility']),
            assets: $assets,
        );
    }

    /**
     * @param  array<string, mixed>|string  $specification
     * @return array<string, mixed>
     */
    private function payload(array|string $specification): array
    {
        if (is_string($specification)) {
            if (strlen($specification) > DesignSpecConstraints::MAX_DOCUMENT_BYTES) {
                $this->fail('document.too_large', '$');
            }

            try {
                $decoded = json_decode($specification, true, DesignSpecConstraints::JSON_DECODE_DEPTH, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('json.invalid: $', previous: $exception);
            }

            if (! is_array($decoded) || array_is_list($decoded)) {
                $this->fail('type.object', '$');
            }

            $payload = [];
            foreach ($decoded as $key => $value) {
                if (! is_string($key)) {
                    $this->fail('type.object', '$');
                }

                $payload[$key] = $value;
            }

            return $payload;
        }

        try {
            $encoded = json_encode($specification, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('json.invalid: $', previous: $exception);
        }

        if (strlen($encoded) > DesignSpecConstraints::MAX_DOCUMENT_BYTES) {
            $this->fail('document.too_large', '$');
        }

        return $specification;
    }

    /** @param array<array-key, mixed> $payload */
    private function assertNestingDepth(array $payload, int $depth = 1): void
    {
        if ($depth > DesignSpecConstraints::MAX_NESTING_DEPTH) {
            $this->fail('document.too_deep', '$');
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $this->assertNestingDepth($value, $depth + 1);
            }
        }
    }

    /** @param array<array-key, mixed> $payload */
    private function rejectForbiddenKeys(array $payload, string $path = '$'): void
    {
        foreach ($payload as $key => $value) {
            $childPath = is_string($key) ? $path . '.' . $key : $path . '[' . $key . ']';
            if (is_string($key) && in_array(strtolower($key), DesignSpecConstraints::FORBIDDEN_KEYS, true)) {
                $this->fail('field.forbidden', $childPath);
            }
            if (is_array($value)) {
                $this->rejectForbiddenKeys($value, $childPath);
            }
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $path, string $schemaObject): array
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            $this->fail('type.object', $path);
        }

        $expected = DesignSpecSchema::keys($schemaObject);
        $actual = array_keys($value);
        $unknown = array_values(array_diff($actual, $expected));
        if ($unknown !== []) {
            $this->fail('field.unknown', $path);
        }
        $missing = array_values(array_diff($expected, $actual));
        if ($missing !== []) {
            $this->fail('field.required', $path . '.' . $missing[0]);
        }

        return $value;
    }

    private function display(mixed $value): DesignSpecDisplayData
    {
        $display = $this->object($value, '$.display', 'display');

        return new DesignSpecDisplayData(
            $this->schemaText($display['name'], '$.display.name', 'display', 'name'),
            $this->schemaText($display['description'], '$.display.description', 'display', 'description'),
        );
    }

    /** @return list<DesignSpecLocaleData> */
    private function locales(mixed $value): array
    {
        $items = $this->list($value, '$.locales', 1, DesignSpecConstraints::MAX_LOCALES);
        $locales = [];
        $seen = [];

        foreach ($items as $index => $item) {
            $path = "$.locales[{$index}]";
            $locale = $this->object($item, $path, 'locale');
            $code = $this->schemaEnum($locale['code'], $path . '.code', 'locale', 'code');
            if (isset($seen[$code])) {
                $this->fail('locale.duplicate', $path . '.code');
            }
            $direction = $this->schemaEnum($locale['direction'], $path . '.direction', 'locale', 'direction');
            if ($direction !== (str_starts_with($code, 'ar') ? 'rtl' : 'ltr')) {
                $this->fail('locale.direction', $path . '.direction');
            }

            $locales[] = new DesignSpecLocaleData($code, $this->schemaText($locale['label'], $path . '.label', 'locale', 'label'), $direction);
            $seen[$code] = true;
        }

        return $locales;
    }

    /**
     * @param  list<string>  $declaredLocaleCodes
     * @return list<DesignSpecSiteData>
     */
    private function sites(mixed $value, array $declaredLocaleCodes): array
    {
        $items = $this->list($value, '$.sites', 1, DesignSpecConstraints::MAX_SITES);
        $sites = [];
        $seen = [];

        foreach ($items as $index => $item) {
            $path = "$.sites[{$index}]";
            $site = $this->object($item, $path, 'site');
            $key = $this->identifier($site['key'], $path . '.key');
            if (isset($seen[$key])) {
                $this->fail('site.duplicate', $path . '.key');
            }

            $localeCodes = $this->localeCodeList($site['localeCodes'], $path . '.localeCodes', $declaredLocaleCodes);
            $defaultLocale = $this->schemaEnum($site['defaultLocale'], $path . '.defaultLocale', 'site', 'defaultLocale');
            if (! in_array($defaultLocale, $localeCodes, true)) {
                $this->fail('site.default_locale', $path . '.defaultLocale');
            }
            $fallbacks = $this->localeCodeList($site['fallbackLocaleCodes'], $path . '.fallbackLocaleCodes', $declaredLocaleCodes);
            $expected = $localeCodes;
            $actual = $fallbacks;
            sort($expected);
            sort($actual);
            if ($fallbacks[0] !== $defaultLocale || $actual !== $expected) {
                $this->fail('site.fallback_chain', $path . '.fallbackLocaleCodes');
            }

            $sites[] = new DesignSpecSiteData(
                $key,
                $this->schemaText($site['name'], $path . '.name', 'site', 'name'),
                $localeCodes,
                $defaultLocale,
                $fallbacks,
            );
            $seen[$key] = true;
        }

        return $sites;
    }

    /**
     * @param  list<string>  $declaredLocaleCodes
     * @return list<string>
     */
    private function localeCodeList(mixed $value, string $path, array $declaredLocaleCodes): array
    {
        $items = $this->list($value, $path, 1, DesignSpecConstraints::MAX_LOCALES);
        $codes = [];
        foreach ($items as $index => $item) {
            $code = $this->schemaEnum($item, $path . "[{$index}]", 'locale', 'code');
            if (! in_array($code, $declaredLocaleCodes, true) || in_array($code, $codes, true)) {
                $this->fail('locale.reference', $path . "[{$index}]");
            }
            $codes[] = $code;
        }

        return $codes;
    }

    /** @return list<DesignSpecAssetData> */
    private function assets(mixed $value): array
    {
        $items = $this->list($value, '$.assets', 0, DesignSpecConstraints::MAX_ASSET_FILES);
        $assets = [];
        $seen = [];
        $totalBytes = 0;

        foreach ($items as $index => $item) {
            $path = "$.assets[{$index}]";
            $asset = $this->object($item, $path, 'asset');
            $id = $this->schemaEnum($asset['id'], $path . '.id', 'asset', 'id');
            $this->identifier($id, $path . '.id');
            if (isset($seen[$id])) {
                $this->fail('asset.duplicate', $path . '.id');
            }
            $catalogue = $this->assetCatalogue[$id];
            if ($catalogue['bytes'] > DesignSpecConstraints::MAX_ASSET_BYTES) {
                $this->fail('asset.too_large', $path . '.id');
            }
            $totalBytes += $catalogue['bytes'];
            if ($totalBytes > DesignSpecConstraints::MAX_TOTAL_ASSET_BYTES) {
                $this->fail('assets.too_large', '$.assets');
            }
            $assets[] = new DesignSpecAssetData($id, $catalogue['kind'], $catalogue['bytes']);
            $seen[$id] = true;
        }

        return $assets;
    }

    /** @param array<string, DesignSpecAssetData> $assetsById */
    private function brand(mixed $value, array $assetsById): DesignSpecBrandData
    {
        $brand = $this->object($value, '$.brand', 'brand');
        $logoAssetId = $this->nullableIdentifier($brand['logoAssetId'], '$.brand.logoAssetId');
        if ($logoAssetId !== null && ($assetsById[$logoAssetId] ?? null)?->kind !== 'image') {
            $this->fail('asset.image_reference', '$.brand.logoAssetId');
        }

        return new DesignSpecBrandData($this->schemaText($brand['name'], '$.brand.name', 'brand', 'name'), $logoAssetId);
    }

    /**
     * @param  list<string>  $localeCodes
     * @param  array<string, DesignSpecAssetData>  $assetsById
     */
    private function typography(mixed $value, array $localeCodes, array $assetsById): DesignSpecTypographyData
    {
        $typography = $this->object($value, '$.typography', 'typography');
        $items = $this->list($typography['locales'], '$.typography.locales', 1, DesignSpecConstraints::MAX_LOCALES);
        $entries = [];
        $seen = [];

        foreach ($items as $index => $item) {
            $path = "$.typography.locales[{$index}]";
            $entry = $this->object($item, $path, 'localeTypography');
            $locale = $this->schemaEnum($entry['locale'], $path . '.locale', 'localeTypography', 'locale');
            if (! in_array($locale, $localeCodes, true) || isset($seen[$locale])) {
                $this->fail('typography.locale', $path . '.locale');
            }
            $entries[] = new DesignSpecLocaleTypographyData(
                $locale,
                $this->typographyRole($entry['heading'], $path . '.heading', $assetsById),
                $this->typographyRole($entry['body'], $path . '.body', $assetsById),
            );
            $seen[$locale] = true;
        }

        $declared = $localeCodes;
        $typed = array_keys($seen);
        sort($declared);
        sort($typed);
        if ($declared !== $typed) {
            $this->fail('typography.incomplete', '$.typography.locales');
        }

        return new DesignSpecTypographyData($entries);
    }

    /** @param array<string, DesignSpecAssetData> $assetsById */
    private function typographyRole(mixed $value, string $path, array $assetsById): DesignSpecTypographyRoleData
    {
        $role = $this->object($value, $path, 'typographyRole');
        $fontAssetId = $this->nullableIdentifier($role['fontAssetId'], $path . '.fontAssetId');
        if ($fontAssetId !== null && ($assetsById[$fontAssetId] ?? null)?->kind !== 'font') {
            $this->fail('asset.font_reference', $path . '.fontAssetId');
        }

        return new DesignSpecTypographyRoleData(
            $this->schemaEnum($role['family'], $path . '.family', 'typographyRole', 'family'),
            $this->schemaEnum($role['style'], $path . '.style', 'typographyRole', 'style'),
            $this->schemaIntegerEnum($role['weight'], $path . '.weight', 'typographyRole', 'weight'),
            $fontAssetId,
        );
    }

    private function palette(mixed $value): DesignSpecPaletteData
    {
        $palette = $this->object($value, '$.palette', 'palette');

        return new DesignSpecPaletteData(
            $this->colorMode($palette['light'], '$.palette.light'),
            $this->colorMode($palette['dark'], '$.palette.dark'),
        );
    }

    private function colorMode(mixed $value, string $path): DesignSpecColorModeData
    {
        $mode = $this->object($value, $path, 'colorMode');
        $colors = [];
        foreach (DesignSpecSchema::keys('colorMode') as $key) {
            $colors[$key] = $this->color($mode[$key], $path . '.' . $key);
        }

        foreach ([['text', 'background'], ['mutedText', 'background'], ['text', 'surface'], ['mutedText', 'surface'], ['onPrimary', 'primary']] as [$foreground, $background]) {
            if ($this->contrastRatio($colors[$foreground], $colors[$background]) < DesignSpecConstraints::MIN_TEXT_CONTRAST) {
                $this->fail('contrast.text', $path . '.' . $foreground);
            }
        }
        foreach ([['largeText', 'background'], ['largeText', 'surface']] as [$foreground, $background]) {
            if ($this->contrastRatio($colors[$foreground], $colors[$background]) < DesignSpecConstraints::MIN_LARGE_TEXT_CONTRAST) {
                $this->fail('contrast.large_text', $path . '.' . $foreground);
            }
        }
        foreach ([['primary', 'background'], ['border', 'background'], ['border', 'surface'], ['focus', 'background'], ['focus', 'surface']] as [$foreground, $background]) {
            if ($this->contrastRatio($colors[$foreground], $colors[$background]) < DesignSpecConstraints::MIN_UI_CONTRAST) {
                $this->fail('contrast.ui', $path . '.' . $foreground);
            }
        }

        return new DesignSpecColorModeData(...array_values($colors));
    }

    private function layout(mixed $value): DesignSpecLayoutData
    {
        $layout = $this->object($value, '$.layout', 'layout');

        return new DesignSpecLayoutData(...array_map(
            fn (string $field): string => $this->schemaEnum($layout[$field], '$.layout.' . $field, 'layout', $field),
            DesignSpecSchema::keys('layout'),
        ));
    }

    private function components(mixed $value): DesignSpecComponentsData
    {
        $components = $this->object($value, '$.components', 'components');

        return new DesignSpecComponentsData(...array_map(
            fn (string $field): string => $this->schemaEnum($components[$field], '$.components.' . $field, 'components', $field),
            DesignSpecSchema::keys('components'),
        ));
    }

    private function accessibility(mixed $value): DesignSpecAccessibilityData
    {
        $policy = $this->object($value, '$.accessibility', 'accessibility');

        return new DesignSpecAccessibilityData(...array_map(
            fn (string $field): string => $this->schemaEnum($policy[$field], '$.accessibility.' . $field, 'accessibility', $field),
            DesignSpecSchema::keys('accessibility'),
        ));
    }

    /** @return list<mixed> */
    private function list(mixed $value, string $path, int $minimum, int $maximum): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) < $minimum || count($value) > $maximum) {
            $this->fail('type.list', $path);
        }

        return $value;
    }

    private function schemaEnum(mixed $value, string $path, string $object, string $field): string
    {
        if (! is_string($value) || ! in_array($value, DesignSpecSchema::enum($object, $field), true)) {
            $this->fail('value.unsupported', $path);
        }

        return $value;
    }

    private function schemaIntegerEnum(mixed $value, string $path, string $object, string $field): int
    {
        if (! is_int($value) || ! in_array($value, DesignSpecSchema::enum($object, $field), true)) {
            $this->fail('value.unsupported', $path);
        }

        return $value;
    }

    private function constant(mixed $value, string $path, string|int $expected): void
    {
        if ($value !== $expected) {
            $this->fail('value.constant', $path);
        }
    }

    private function schemaText(mixed $value, string $path, string $object, string $field): string
    {
        $definition = DesignSpecSchema::property($object, $field);
        $minimumLength = $definition['minLength'] ?? null;
        $maximumLength = $definition['maxLength'] ?? null;
        if (! is_int($minimumLength) || ! is_int($maximumLength)) {
            throw new LogicException('DesignSpec text schema is invalid.');
        }
        if (! is_string($value)
            || mb_strlen($value) < $minimumLength
            || mb_strlen($value) > $maximumLength
            || preg_match('/' . DesignSpecConstraints::TEXT_PATTERN . '/uD', $value) !== 1) {
            $this->fail('text.invalid', $path);
        }

        return $value;
    }

    private function nullableIdentifier(mixed $value, string $path): ?string
    {
        return $value === null ? null : $this->identifier($value, $path);
    }

    private function identifier(mixed $value, string $path): string
    {
        if (! is_string($value)
            || strlen($value) > DesignSpecConstraints::MAX_IDENTIFIER_LENGTH
            || preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $value) !== 1) {
            $this->fail('identifier.invalid', $path);
        }

        return $value;
    }

    private function color(mixed $value, string $path): string
    {
        if (! is_string($value) || preg_match('/^#[0-9A-F]{6}$/D', $value) !== 1) {
            $this->fail('color.invalid', $path);
        }

        return $value;
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $foregroundLuminance = $this->luminance($foreground);
        $backgroundLuminance = $this->luminance($background);

        return (max($foregroundLuminance, $backgroundLuminance) + 0.05)
            / (min($foregroundLuminance, $backgroundLuminance) + 0.05);
    }

    private function luminance(string $color): float
    {
        $channels = [hexdec(substr($color, 1, 2)) / 255, hexdec(substr($color, 3, 2)) / 255, hexdec(substr($color, 5, 2)) / 255];
        $linear = array_map(
            static fn (float $channel): float => $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4,
            $channels,
        );

        return (0.2126 * $linear[0]) + (0.7152 * $linear[1]) + (0.0722 * $linear[2]);
    }

    private function fail(string $code, string $path): never
    {
        throw new InvalidArgumentException($code . ': ' . $path);
    }
}
