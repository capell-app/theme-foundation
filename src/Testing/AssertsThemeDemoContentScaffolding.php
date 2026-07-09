<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Testing;

use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;

/**
 * Wave 2.8 — shared Pest assertions for a freshly generated theme's
 * scaffolded test stubs (`capell:make-theme`). Deliberately narrow: these
 * are the two checks every new theme's own suite needs on day one, before
 * any bespoke widget or preset tests exist — "does this render at all" and
 * "does it cover all 7 demo surfaces" (the Wave 3.1
 * `ThemeDemoContentContractTest` fleet contract, checked here per-theme so
 * a new theme catches the gap immediately instead of only at the fleet
 * gate).
 *
 * Mix this trait into a Pest test file via
 * `uses(AssertsThemeDemoContentScaffolding::class);` and call the relevant
 * `assert*` method from an `it()` block.
 */
trait AssertsThemeDemoContentScaffolding
{
    /**
     * The 7 demo surfaces every `ProvidesThemeDemoContent` implementation
     * must cover, per the Wave 3.1 fleet contract (homepage, directory,
     * detail, contact, empty, not-found, cta).
     *
     * @var list<string>
     */
    private static array $requiredDemoSurfaces = [
        'homepage',
        'directory',
        'detail',
        'contact',
        'empty',
        'not-found',
        'cta',
    ];

    /**
     * Asserts that `$demoContentProvider->definitions()` resolves without
     * throwing for the given theme key/name/base URL, and that the result
     * is a non-empty list of {@see ThemeDemoPageDefinition} instances.
     */
    protected function assertThemeDemoContentRendersWithoutThrowing(
        ProvidesThemeDemoContent $demoContentProvider,
        string $themeKey,
        string $themeName,
        string $baseUrl = 'https://example.test',
    ): void {
        $definitions = $demoContentProvider->definitions($themeKey, $themeName, $baseUrl);

        $this->assertNotEmpty($definitions, "{$themeKey}: ProvidesThemeDemoContent::definitions() returned no page definitions.");

        foreach ($definitions as $definition) {
            $this->assertInstanceOf(
                ThemeDemoPageDefinition::class,
                $definition,
                "{$themeKey}: every definitions() entry must be a ThemeDemoPageDefinition instance.",
            );
        }
    }

    /**
     * Asserts that all 7 required demo surfaces (homepage, directory,
     * detail, contact, empty, not-found, cta) are present among the
     * provider's definitions for the given theme key/name/base URL.
     */
    protected function assertAllDemoSurfacesArePresent(
        ProvidesThemeDemoContent $demoContentProvider,
        string $themeKey,
        string $themeName,
        string $baseUrl = 'https://example.test',
    ): void {
        $definitions = $demoContentProvider->definitions($themeKey, $themeName, $baseUrl);

        $presentSurfaces = array_values(array_unique(array_map(
            static fn (ThemeDemoPageDefinition $definition): string => $definition->surface,
            $definitions,
        )));

        $missingSurfaces = array_values(array_diff(self::$requiredDemoSurfaces, $presentSurfaces));

        $this->assertSame(
            [],
            $missingSurfaces,
            "{$themeKey}: missing required demo surfaces: " . implode(', ', $missingSurfaces) . '.',
        );
    }
}
