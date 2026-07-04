<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Editor;

/**
 * Wave 10.1 — the shared editor schema every theme declares in its
 * ThemeDefinitionData::$frontend['editor'].
 *
 * It names which brand tokens a theme exposes to Theme Studio, how they group
 * into form sections, and the allowed values for each — so a future Filament
 * configurator can render controls generically from this declaration instead
 * of hardcoding Foundation's ten. Declaring it here (rather than copy-pasting
 * the same array into twenty providers) keeps the fleet consistent; a theme
 * with identity-specific controls calls withExtraTokens() to add its own.
 *
 * The default token set is exactly the six that ThemeTokenConsumptionTest
 * proves every theme consumes, so a theme adopting this schema stays within
 * the token-consumption contract by construction.
 */
final class StandardThemeEditorSchema
{
    /**
     * @return array{groups: array<string, list<string>>, tokens: array<string, array{options: list<string>}>}
     */
    public static function definition(): array
    {
        return [
            'groups' => [
                'layout' => ['spacing', 'layoutPresentation', 'cardStyle', 'cardDensity'],
                'typography' => ['headingScale'],
                'media' => ['overlayTreatment'],
            ],
            'tokens' => [
                'spacing' => ['options' => ['compact', 'cozy', 'spacious']],
                'layoutPresentation' => ['options' => ['contained', 'full-bleed']],
                'cardStyle' => ['options' => ['flat', 'outlined', 'raised']],
                'cardDensity' => ['options' => ['compact', 'comfortable']],
                'headingScale' => ['options' => ['compact', 'default', 'expressive']],
                'overlayTreatment' => ['options' => ['none', 'subtle', 'strong']],
            ],
        ];
    }

    /**
     * Returns the standard schema with theme-specific controls merged in — a
     * new group and its token definitions — for themes whose identity demands
     * controls beyond the shared set (e.g. liquid-glass depth tiers). Each
     * extra token must be consumed by that theme's own CSS, or the
     * editor-schema consumption guard will fail.
     *
     * @param  array<string, list<string>>  $groups
     * @param  array<string, array{options: list<string>}>  $tokens
     * @return array{groups: array<string, list<string>>, tokens: array<string, array{options: list<string>}>}
     */
    public static function withExtraTokens(array $groups, array $tokens): array
    {
        $schema = self::definition();

        $schema['groups'] = [...$schema['groups'], ...$groups];
        $schema['tokens'] = [...$schema['tokens'], ...$tokens];

        return $schema;
    }

    /**
     * Every token key referenced by the standard schema's groups — the set the
     * consumption guard checks against each theme's CSS.
     *
     * @return list<string>
     */
    public static function tokenKeys(): array
    {
        $keys = [];

        foreach (self::definition()['groups'] as $groupTokens) {
            foreach ($groupTokens as $tokenKey) {
                $keys[] = $tokenKey;
            }
        }

        return array_values(array_unique($keys));
    }
}
