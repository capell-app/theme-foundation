<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePresetData;
use Capell\FoundationTheme\Providers\FoundationThemeServiceProvider;

final class UnknownFontPresetFixtureProvider
{
    public static function definition(): ThemeDefinitionData
    {
        $definition = FoundationThemeServiceProvider::definition();
        $preset = static fn (string $key, array $values = [], ?string $pairing = null): ThemePresetData => new ThemePresetData(
            key: $key,
            name: $key,
            description: $key,
            previewImage: '/fixture.webp',
            values: $values,
            typePairing: $pairing,
        );

        return new ThemeDefinitionData(
            key: $definition->key,
            name: $definition->name,
            description: $definition->description,
            package: $definition->package,
            previewImage: $definition->previewImage,
            tags: $definition->tags,
            bestFit: $definition->bestFit,
            presets: [
                $preset('good', ['headingFont' => 'fraunces', 'bodyFont' => 'source-serif']),
                $preset('bad-heading', ['headingFont' => 'source-serif-4', 'bodyFont' => 'inter']),
                $preset('bad-pairing', pairing: 'fraunces+comic-sans'),
            ],
            extends: $definition->extends,
        );
    }
}
