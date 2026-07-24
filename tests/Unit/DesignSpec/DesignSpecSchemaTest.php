<?php

declare(strict_types=1);

use Capell\FoundationTheme\Support\DesignSpec\DesignSpecConstraints;
use Capell\FoundationTheme\Support\DesignSpec\DesignSpecSchema;

it('publishes a closed frozen foundation schema without defaults or launch controls', function (): void {
    $schema = DesignSpecSchema::toArray();

    expect(data_get($schema, 'additionalProperties'))->toBeFalse()
        ->and(data_get($schema, 'required'))->toBe(DesignSpecSchema::keys('root'))
        ->and(data_get($schema, 'properties.schemaVersion.const'))->toBe(1)
        ->and(data_get($schema, 'properties.template.const'))->toBe('foundation')
        ->and(data_get($schema, 'properties.sites.maxItems'))->toBe(DesignSpecConstraints::MAX_SITES)
        ->and(data_get($schema, 'properties.locales.maxItems'))->toBe(DesignSpecConstraints::MAX_LOCALES)
        ->and(data_get($schema, 'properties.assets.maxItems'))->toBe(DesignSpecConstraints::MAX_ASSET_FILES);

    expect(data_get($schema, '$defs.components.maxProperties'))->toBe(DesignSpecConstraints::MAX_COMPONENT_SELECTIONS)
        ->and(data_get($schema, '$defs.colorMode.maxProperties'))->toBe(DesignSpecConstraints::MAX_PALETTE_COLORS)
        ->and(data_get($schema, '$defs.colorMode.required'))->toContain('largeText');

    foreach (foundationThemeJsonObject(data_get($schema, '$defs')) as $name => $definition) {
        if (! is_string($name) || ! is_array($definition)) {
            throw new RuntimeException('Invalid DesignSpec schema definition.');
        }

        if (($definition['type'] ?? null) === 'object') {
            expect($definition['additionalProperties'] ?? null)->toBeFalse()
                ->and($definition['required'] ?? null)->toBe(DesignSpecSchema::keys($name));
        }
    }

    $encoded = strtolower((string) json_encode($schema, JSON_THROW_ON_ERROR));
    expect($encoded)->not->toContain('"default"')
        ->not->toContain('"launch"')
        ->not->toContain('"publish"')
        ->not->toContain('"deploy"')
        ->not->toContain('assetpath')
        ->not->toContain('fontassetpath');
});

it('publishes only server-owned canonical asset identifiers', function (): void {
    $assetIds = DesignSpecSchema::enum('asset', 'id');

    expect($assetIds)->toBe(array_keys(DesignSpecConstraints::ASSET_CATALOGUE));

    foreach ($assetIds as $assetId) {
        expect($assetId)->toMatch('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/')
            ->not->toContain('.');
    }

    expect(array_sum(array_column(DesignSpecConstraints::ASSET_CATALOGUE, 'bytes')))
        ->toBeLessThanOrEqual(DesignSpecConstraints::MAX_TOTAL_ASSET_BYTES);
    foreach (DesignSpecConstraints::ASSET_CATALOGUE as $asset) {
        expect($asset['bytes'])->toBeLessThanOrEqual(DesignSpecConstraints::MAX_ASSET_BYTES);
    }
});

it('freezes the reviewed DesignSpec fixtures', function (): void {
    $fixtures = dirname(__DIR__, 2) . '/Fixtures/design-spec';

    expect(hash_file('sha256', $fixtures . '/v0-minimal.json'))->toBe('6a598aafc6f48077fe2bbf87ad0e6f58726fe6fbce0682d067258ddf2080975b')
        ->and(hash_file('sha256', $fixtures . '/v1-canonical.json'))->toBe('a1509bd3c355ec1ffe9dd3cf9059b0980acf41f4bf4d9c46cbe63fa955f952c2')
        ->and(hash_file('sha256', $fixtures . '/v1-canonical.canonical.json'))->toBe('133b3cd9b6d07f96072810ab9b525e001dd9a59265ab8d55657f8b1cd4e9f22c')
        ->and(hash_file('sha256', $fixtures . '/v1-two-sites-two-locales.json'))->toBe('9ff26c42c746bb1c8b427d1647ef878c1e73eca6d05dc868bb525118020fb2b3');
});

it('uses the published schema as the runtime source for keys and enums', function (): void {
    expect(DesignSpecSchema::keys('accessibility'))->toBe(['reducedMotion', 'focusIndicator', 'landmarks', 'headingHierarchy'])
        ->and(DesignSpecSchema::enum('accessibility', 'reducedMotion'))->toBe(['respect'])
        ->and(DesignSpecSchema::enum('layout', 'darkMode'))->toBe(DesignSpecConstraints::LAYOUT_OPTIONS['darkMode'])
        ->and(DesignSpecSchema::enum('typographyRole', 'style'))->toBe(DesignSpecConstraints::FONT_STYLES)
        ->and(DesignSpecSchema::enum('typographyRole', 'weight'))->toBe(DesignSpecConstraints::FONT_WEIGHTS)
        ->and(DesignSpecSchema::keys('components'))->toBe(array_keys(DesignSpecConstraints::COMPONENT_VARIANTS))
        ->and(DesignSpecSchema::enum('components', 'hero'))->toBe(DesignSpecConstraints::COMPONENT_VARIANTS['hero']);
});

it('publishes the exact shared runtime text constraints', function (string $field): void {
    [$object, $property] = explode('.', $field, 2);
    $schema = DesignSpecSchema::property($object, $property);
    $constraint = DesignSpecConstraints::TEXT_FIELDS[$field];

    expect($schema['minLength'])->toBe($constraint['minimum'])
        ->and($schema['maxLength'])->toBe($constraint['maximum'])
        ->and($schema['pattern'])->toBe(DesignSpecConstraints::TEXT_PATTERN);
})->with(array_keys(DesignSpecConstraints::TEXT_FIELDS));
