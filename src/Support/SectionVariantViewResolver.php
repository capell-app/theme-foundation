<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

/**
 * Resolves the section view name a theme's `widget.section` dispatcher should
 * include, honouring the optional `meta['variant']` an editor picks through
 * `SectionVariantSchemaExtender`.
 *
 * Themes declare their variants in `ThemeDefinitionData->frontend['sectionVariants']`
 * and ship one Blade per variant named `{type}--{variant}`. This resolver
 * replaces the hand-rolled `$variantViews` lookup maps each theme used to
 * inline in its section Blade.
 *
 * The exists-check is the safety net for stale state: switching theme leaves
 * `meta['variant']` pointing at a variant the new theme does not ship, and the
 * base `{type}` view is then used instead of erroring.
 */
final class SectionVariantViewResolver
{
    /**
     * @param  array<array-key, mixed>  $section  Widget meta, as stored under the `meta` state path.
     * @return string|null The view name relative to `{$namespace}::sections.`, or null when nothing renders.
     */
    public static function resolve(string $namespace, array $section): ?string
    {
        $type = $section['type'] ?? null;

        if (! is_string($type) || $type === '') {
            return null;
        }

        $variant = $section['variant'] ?? null;

        if (is_string($variant) && $variant !== '' && $variant !== 'default') {
            $variantView = $type . '--' . $variant;

            if (view()->exists($namespace . '::sections.' . $variantView)) {
                return $variantView;
            }
        }

        return view()->exists($namespace . '::sections.' . $type) ? $type : null;
    }
}
