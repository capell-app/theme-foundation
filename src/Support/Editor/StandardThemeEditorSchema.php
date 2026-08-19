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
 * the token-consumption contract by construction. Themes can opt into extra
 * controls with withExtraTokens().
 */
final class StandardThemeEditorSchema
{
    /**
     * Broad motion-intensity values observed in existing theme presets.
     *
     * The Foundation settings surface intentionally only offers a smaller
     * subset. These options are therefore exposed as Theme Studio controls for
     * themes that opt in via withExtraTokens().
     *
     * @var list<string>
     */
    public const array MOTION_INTENSITY_OPTIONS = [
        'none',
        'minimal',
        'subtle',
        'energetic',
        'expressive',
        'lively',
        'balanced',
    ];

    /**
     * Media-treatment values already emitted by theme presets.
     *
     * @var list<string>
     */
    public const array MEDIA_TREATMENT_OPTIONS = [
        'curation-feed',
        'duotone',
        'feature-slab',
        'framed',
        'illustrated',
        'irregular-index',
        'motion-preview',
        'natural',
        'photographic',
        'quiet-image-grid',
        'rounded-screenshot',
        'score-media',
        'standard',
        'technical',
        'thumbnail-grid',
    ];

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
                'spacing' => ['options' => ['compact', 'snug', 'tight', 'balanced', 'relaxed', 'generous', 'airy', 'spacious']],
                'layoutPresentation' => ['options' => ['structured', 'editorial', 'grid', 'full-bleed']],
                'cardStyle' => ['options' => ['flat', 'outlined', 'raised', 'bordered', 'elevated', 'hairline', 'raw', 'scoreboard', 'sharp', 'understated']],
                'cardDensity' => ['options' => ['compact', 'comfortable', 'spacious']],
                'headingScale' => ['options' => ['compact', 'balanced', 'expressive']],
                'overlayTreatment' => ['options' => ['none', 'subtle', 'strong', 'scrim', 'gradient']],
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
     * Shared extension for the missing Theme Studio controls currently present
     * in theme presets (`motionIntensity`, `mediaTreatment`) but absent from the
     * core schema.
     *
     * @return array{groups: array<string, list<string>>, tokens: array<string, array{options: list<string>}>}
     */
    public static function withMotionAndMediaControls(): array
    {
        return self::withExtraTokens(
            groups: [
                'motion' => ['motionIntensity'],
                'media' => [...self::definition()['groups']['media'], 'mediaTreatment'],
            ],
            tokens: [
                'motionIntensity' => ['options' => self::MOTION_INTENSITY_OPTIONS],
                'mediaTreatment' => ['options' => self::MEDIA_TREATMENT_OPTIONS],
            ],
        );
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
