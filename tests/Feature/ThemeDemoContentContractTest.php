<?php

declare(strict_types=1);

use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Illuminate\Support\Str;

require_once dirname(__DIR__, 4) . '/tests/Packages/Support/ThemeLayoutNativeSupport.php';

/*
|--------------------------------------------------------------------------
| Fleet-wide theme demo content contract (Wave 3.1)
|--------------------------------------------------------------------------
|
| Auto-discovers every theme's ProvidesThemeDemoContent implementation and
| asserts the baseline every Foundation-derived theme must meet so a theme
| silently dropping a surface (or shipping an empty one) is caught here
| rather than surfacing as a broken live render:
|   - all seven Foundation surfaces are present,
|   - each surface carries an ordered, non-empty section list (or, for a
|     theme converted to layout-builder, non-empty containers), and
|   - each surface carries real navigation + footer chrome.
|
| This is the baseline completeness bar. The deeper "reads as an individual,
| non-generic vertical site" bar (minimum section counts, signature-section
| minimums, weak-copy scanning, cross-surface brand consistency) is covered
| separately by tests/Arch/ThemeDemoCompletenessContractTest.php.
|
*/

/** @var list<string> */
const FLEET_FOUNDATION_SURFACES = ['homepage', 'directory', 'detail', 'contact', 'empty', 'not-found', 'cta'];

/**
 * Themes excluded from the fleet-wide demo content contract.
 *
 * @var list<string>
 */
const FLEET_DEMO_CONTRACT_EXCLUSIONS = [
    // Non-standard packs, not Foundation-derived verticals.
    'inertia-bookings',
    'inertia-bookings-react',
    'inertia-bookings-vue',
];

/**
 * The demo content provider FQCN for a theme slug.
 *
 * Every vertical theme follows the `Capell\Theme<Studio>\Support\Demo\<Studio>DemoContent`
 * naming convention. Foundation is a documented exception: it predates that
 * convention and keeps its own namespace (`Capell\FoundationTheme`, not
 * `Capell\ThemeFoundation`), so its provider lives at
 * `Capell\FoundationTheme\Support\Demo\FoundationDemoContent` instead.
 */
function fleetDemoContentProviderClass(string $slug, string $studio): string
{
    return $slug === 'foundation'
        ? 'Capell\\FoundationTheme\\Support\\Demo\\FoundationDemoContent'
        : "Capell\\Theme{$studio}\\Support\\Demo\\{$studio}DemoContent";
}

dataset('fleet_themes_for_demo_contract', function (): array {
    $root = dirname(__DIR__, 4);
    $cases = [];

    foreach (glob($root . '/packages/theme-*/capell.json') ?: [] as $manifestPath) {
        $slug = substr(basename(dirname($manifestPath)), mb_strlen('theme-'));

        if (in_array($slug, FLEET_DEMO_CONTRACT_EXCLUSIONS, true)) {
            continue;
        }

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        if (($manifest['kind'] ?? null) !== 'theme') {
            continue;
        }

        $cases[$slug] = [$slug];
    }

    return $cases;
});

it('theme ships a demo content provider covering every foundation surface', function (string $slug): void {
    $studio = Str::studly($slug);
    $providerClass = fleetDemoContentProviderClass($slug, $studio);

    expect(class_exists($providerClass))->toBeTrue(
        "Theme [{$slug}] has no demo content provider. Expected {$providerClass} implementing ProvidesThemeDemoContent.",
    );

    $provider = new $providerClass;
    expect($provider)->toBeInstanceOf(ProvidesThemeDemoContent::class);
    throw_unless($provider instanceof ProvidesThemeDemoContent, RuntimeException::class, "Theme [{$slug}] demo content provider must implement ProvidesThemeDemoContent.");

    $definitions = $provider->definitions($slug, Str::headline($slug), "https://{$slug}.test");

    $bySurface = [];
    foreach ($definitions as $definition) {
        $bySurface[$definition->surface] = $definition;
    }

    expect(array_keys($bySurface))->toEqualCanonicalizing(FLEET_FOUNDATION_SURFACES);

    foreach (FLEET_FOUNDATION_SURFACES as $surface) {
        $definition = $bySurface[$surface];

        if (in_array($slug, themesConvertedToLayoutBuilder(), true)) {
            expect($definition->hasContainers())->toBeTrue(
                "Theme [{$slug}] surface [{$surface}] is layout-native and must carry non-empty layout-builder containers.",
            );

            continue;
        }

        $renderData = $definition->renderData;
        $sections = $renderData['sections'] ?? null;

        expect(is_array($sections) && array_is_list($sections) && $sections !== [])->toBeTrue(
            "Theme [{$slug}] surface [{$surface}] must carry an ordered, non-empty render_data['sections'] list.",
        );
        throw_unless(is_array($sections), RuntimeException::class, "Theme [{$slug}] surface [{$surface}] sections must be an array.");

        foreach ($sections as $section) {
            expect(is_array($section) && is_string($section['type'] ?? null) && $section['type'] !== '')->toBeTrue(
                "Theme [{$slug}] surface [{$surface}] has a section without a type.",
            );
        }

        $navigation = $renderData['navigation'] ?? [];
        $footer = $renderData['footer'] ?? [];

        throw_unless(is_array($navigation), RuntimeException::class, "Theme [{$slug}] surface [{$surface}] navigation must be an array.");
        throw_unless(is_array($footer), RuntimeException::class, "Theme [{$slug}] surface [{$surface}] footer must be an array.");

        $navItems = $navigation['items'] ?? [];
        $brandName = $navigation['brandName'] ?? null;
        $footerColumns = $footer['columns'] ?? [];

        expect(is_string($brandName) && $brandName !== '')->toBeTrue(
            "Theme [{$slug}] surface [{$surface}] needs a real navigation brandName.",
        );
        expect(count(is_array($navItems) ? $navItems : []))->toBeGreaterThanOrEqual(
            3,
            "Theme [{$slug}] surface [{$surface}] needs real navigation chrome (at least 3 items).",
        );
        expect(count(is_array($footerColumns) ? $footerColumns : []))->toBeGreaterThanOrEqual(
            2,
            "Theme [{$slug}] surface [{$surface}] needs real footer chrome (at least 2 columns).",
        );
    }
})->with('fleet_themes_for_demo_contract');
