<?php

declare(strict_types=1);

use Capell\FoundationTheme\Contracts\ProvidesThemeDemoContent;
use Capell\FoundationTheme\Support\Demo\FoundationDemoContent;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;
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
 * Themes whose contact surface is part of the first form-builder integration wave.
 *
 * @var list<string>
 */
const FLEET_FORM_BUILDER_THEMES = [
    'foundation',
    'agency',
    'brutalist',
    'directory',
    'onepage',
    'portfolio',
    'showreel',
    'submissions',
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
    $themeKey = $slug === 'foundation' ? 'default' : $slug;
    $providerClass = fleetDemoContentProviderClass($slug, $studio);

    expect(class_exists($providerClass))->toBeTrue(
        "Theme [{$slug}] has no demo content provider. Expected {$providerClass} implementing ProvidesThemeDemoContent.",
    );

    $provider = new $providerClass;
    expect($provider)->toBeInstanceOf(ProvidesThemeDemoContent::class);
    throw_unless($provider instanceof ProvidesThemeDemoContent, RuntimeException::class, "Theme [{$slug}] demo content provider must implement ProvidesThemeDemoContent.");

    $definitions = $provider->definitions($themeKey, Str::headline($slug), "https://{$slug}.test");

    $bySurface = [];
    foreach ($definitions as $definition) {
        $bySurface[$definition->surface] = $definition;
    }

    foreach (FLEET_FOUNDATION_SURFACES as $surface) {
        expect($bySurface)->toHaveKey($surface);
    }

    foreach (FLEET_FOUNDATION_SURFACES as $surface) {
        $definition = $bySurface[$surface];

        if (in_array($themeKey, themesConvertedToLayoutBuilder(), true)) {
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

it('priority themes ship a populated form-builder contact section', function (string $slug): void {
    $studio = Str::studly($slug);
    $providerClass = fleetDemoContentProviderClass($slug, $studio);
    $provider = new $providerClass;

    throw_unless($provider instanceof ProvidesThemeDemoContent, RuntimeException::class, "Theme [{$slug}] demo content provider must implement ProvidesThemeDemoContent.");

    $contactDefinition = collect($provider->definitions($slug, Str::headline($slug), "https://{$slug}.test"))
        ->firstWhere('surface', 'contact');

    expect($contactDefinition)->not->toBeNull();
    throw_if($contactDefinition === null, RuntimeException::class, "Theme [{$slug}] must define a contact surface.");

    $sections = $contactDefinition->renderData['sections'] ?? [];
    throw_unless(is_array($sections), RuntimeException::class, "Theme [{$slug}] contact sections must be an array.");

    $formSection = collect($sections)->first(
        fn (mixed $section): bool => is_array($section) && ($section['type'] ?? null) === 'form',
    );

    throw_unless(is_array($formSection), RuntimeException::class, "Theme [{$slug}] form section must be an array.");
    $fields = $formSection['fields'] ?? null;
    throw_unless(is_array($fields), RuntimeException::class, "Theme [{$slug}] form fields must be an array.");

    expect($formSection)
        ->toBeArray()
        ->and($formSection['form_handle'] ?? null)->toBeString()->not->toBeEmpty()
        ->and(count($fields))->toBeGreaterThanOrEqual(4)
        ->and($formSection['fallback_url'] ?? null)->toBeString()->not->toBeEmpty();
})->with(collect(FLEET_FORM_BUILDER_THEMES)->mapWithKeys(
    fn (string $slug): array => [$slug => [$slug]],
)->all());

it('foundation demonstrates a credible site instead of describing its implementation', function (): void {
    $definitions = (new FoundationDemoContent)->definitions(
        themeKey: 'default',
        themeName: 'Foundation',
        baseUrl: 'https://foundation.test',
    );

    $visibleCopy = collect($definitions)
        ->flatMap(static fn (ThemeDemoPageDefinition $definition): array => [
            $definition->name,
            $definition->title,
            $definition->content,
            json_encode($definition->renderData, JSON_THROW_ON_ERROR),
        ])
        ->implode(' ');

    expect(mb_strtolower($visibleCopy))
        ->not->toContain('foundation theme')
        ->not->toContain('foundation docs')
        ->not->toContain('theme studio')
        ->not->toContain('layout contract')
        ->not->toContain('section variant')
        ->not->toContain('capell starter theme')
        ->not->toContain('capell:make-theme')
        ->and($visibleCopy)
        ->not->toContain('—')
        ->not->toContain('–')
        ->not->toContain('·');
});

it('foundation keeps portable page copy without repeating the hero title', function (): void {
    $definitions = (new FoundationDemoContent)->definitions(
        themeKey: 'default',
        themeName: 'Foundation',
        baseUrl: 'https://foundation.test',
    );

    foreach ($definitions as $definition) {
        $mainContainer = $definition->containers['main'] ?? null;

        if (! is_array($mainContainer)) {
            throw new RuntimeException("Foundation surface [{$definition->surface}] must contain a main container.");
        }

        $containerWidgets = $mainContainer['widgets'] ?? null;

        if (! is_array($containerWidgets)) {
            throw new RuntimeException("Foundation surface [{$definition->surface}] must contain main widgets.");
        }

        $pageContentWidget = collect($containerWidgets)->first(
            static fn (mixed $widget): bool => is_array($widget)
                && ($widget['widget_key'] ?? null) === 'page-content',
        );

        if (! is_array($pageContentWidget)) {
            throw new RuntimeException("Foundation surface [{$definition->surface}] must contain a page-content widget.");
        }

        $widgetMeta = $pageContentWidget['meta'] ?? null;

        if (! is_array($widgetMeta)) {
            throw new RuntimeException("Foundation surface [{$definition->surface}] page-content widget must contain metadata.");
        }

        expect($widgetMeta['page_content'] ?? null)->toBe(
            ['content'],
            "Foundation surface [{$definition->surface}] must omit the duplicated page title from its page-content widget.",
        );
    }
});

it('foundation chrome links to its seeded buyer journeys', function (): void {
    $definitions = (new FoundationDemoContent)->definitions(
        themeKey: 'default',
        themeName: 'Foundation',
        baseUrl: 'https://foundation.test',
    );

    foreach ($definitions as $definition) {
        $navigation = $definition->renderData['navigation'] ?? null;
        $footer = $definition->renderData['footer'] ?? null;

        throw_unless(is_array($navigation), RuntimeException::class, "{$definition->surface} navigation missing.");
        throw_unless(is_array($footer), RuntimeException::class, "{$definition->surface} footer missing.");

        expect($navigation['items'] ?? null)->toBe([
            ['label' => 'Work', 'url' => '/theme-default#features'],
            ['label' => 'Field notes', 'url' => '/theme-default-directory'],
            ['label' => 'Approach', 'url' => '/theme-default#proof'],
            ['label' => 'Contact', 'url' => '/theme-default-contact'],
        ])->and($navigation['ctaUrl'] ?? null)->toBe('/theme-default-contact')
            ->and(data_get($footer, 'columns.0.links'))->toBe([
                ['label' => 'How we work', 'url' => '/theme-default#proof'],
                ['label' => 'Field notes', 'url' => '/theme-default-directory'],
            ]);
    }
});

it('foundation local calls to action resolve to sections on the current surface', function (): void {
    $definitions = (new FoundationDemoContent)->definitions(
        themeKey: 'default',
        themeName: 'Foundation',
        baseUrl: 'https://foundation.test',
    );

    foreach ($definitions as $definition) {
        $sections = $definition->sections();
        $sectionTypes = collect($sections)
            ->pluck('type')
            ->filter(static fn (mixed $type): bool => is_string($type))
            ->all();

        foreach ($sections as $section) {
            $actions = $section['actions'] ?? null;

            if (! is_array($actions)) {
                continue;
            }

            foreach ($actions as $action) {
                $url = is_array($action) ? ($action['url'] ?? null) : null;

                if (! is_string($url) || ! str_starts_with($url, '#')) {
                    continue;
                }

                expect(in_array(mb_substr($url, 1), $sectionTypes, true))->toBeTrue(
                    "Foundation surface [{$definition->surface}] links to missing local section [{$url}].",
                );
            }
        }
    }
});
