<?php

declare(strict_types=1);

use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\ThemeStudio\Contracts\SectionRenderer;
use Capell\Core\ThemeStudio\Contracts\ThemeSection;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\HeroSectionData;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePageData;
use Capell\Core\ThemeStudio\Exceptions\SectionRendererNotFoundException;
use Capell\Core\ThemeStudio\Exceptions\ThemeNotFoundException;
use Capell\Core\ThemeStudio\Rendering\BladeThemeRenderer;
use Capell\Core\ThemeStudio\Rendering\ViewSectionRenderer;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\FoundationTheme\Tests\Fixtures\ThemeRegistryTestStringSectionRenderer;

it('registers theme definitions renderers and section renderers by theme key', function (): void {
    $registry = new ThemeRegistry;
    $sectionRenderer = new ViewSectionRenderer('liquid-glass', 'hero', 'missing-view');
    $themeRenderer = new BladeThemeRenderer('liquid-glass', 'missing-layout', ['hero' => $sectionRenderer]);

    $registry->register(
        new ThemeDefinitionData(
            key: 'liquid-glass',
            name: 'Liquid Glass',
            description: 'Translucent visual system',
            package: 'capell-app/theme-liquid-glass',
            previewImage: '/preview.jpg',
            tags: ['Trust'],
            bestFit: ['B2B'],
            includedSections: ['hero'],
            presets: [],
        ),
        $themeRenderer,
        [$sectionRenderer],
    );

    expect($registry->has('liquid-glass'))->toBeTrue()
        ->and($registry->definition('liquid-glass')->package)->toBe('capell-app/theme-liquid-glass')
        ->and($registry->renderer('liquid-glass'))->toBe($themeRenderer)
        ->and($registry->sectionRenderer('liquid-glass', 'hero'))->toBe($sectionRenderer);
});

it('throws for missing registered themes', function (): void {
    expect(fn (): mixed => (new ThemeRegistry)->definition('missing'))
        ->toThrow(ThemeNotFoundException::class);
});

it('renders fallback section keys without coupling content to one renderer package', function (): void {
    $fallbackRenderer = new ViewSectionRenderer('corporate', 'content-listing', 'missing-view');
    $renderer = new BladeThemeRenderer('corporate', 'missing-layout', ['content-listing' => $fallbackRenderer]);

    $section = new class implements ThemeSection
    {
        public function key(): string
        {
            return 'pricing';
        }

        public function fallbackKey(): string
        {
            return 'content-listing';
        }

        public function toViewData(): array
        {
            return ['section' => $this];
        }
    };

    $html = $renderer->render(new ThemePageData(
        title: 'Example',
        brand: new BrandProfileData,
        sections: [$section],
    ));

    expect($html)->toContain('data-theme="corporate"')
        ->and($html)->toContain('data-section="pricing"');
});

it('throws renderer failures when a first-party section renderer is marked loud', function (): void {
    $renderer = new ViewSectionRenderer('corporate', 'hero', 'missing-view', failLoudly: true);

    expect(fn (): string => $renderer->render(new HeroSectionData(heading: 'Broken view')))
        ->toThrow(InvalidArgumentException::class);
});

it('keeps shared section data portable across theme packages', function (): void {
    $section = new HeroSectionData(
        heading: 'Same content, different theme',
        actions: [['label' => 'Preview', 'url' => '/preview']],
    );

    expect($section->key())->toBe('hero')
        ->and($section->toViewData()['section'])->toBe($section);
});

it('stores runtime metadata with theme definitions', function (): void {
    $definition = new ThemeDefinitionData(
        key: 'inertia-test',
        name: 'Inertia Test',
        description: 'Runtime-aware test theme.',
        package: 'capell-app/theme-inertia-test',
        previewImage: '/preview.jpg',
        tags: [],
        bestFit: [],
        includedSections: [],
        presets: [],
        runtime: FrontendRuntime::Inertia,
        frontend: ['entry' => 'resources/js/app.ts'],
    );

    expect($definition->runtime->value)->toBe('inertia')
        ->and($definition->frontend)->toBe(['entry' => 'resources/js/app.ts']);
});

it('prefers child section renderers before inherited foundation renderers', function (): void {
    $registry = new ThemeRegistry;
    app()->instance(ThemeRegistry::class, $registry);

    $foundationHero = new ThemeRegistryTestStringSectionRenderer('default', 'hero', 'foundation hero');
    $childHero = new ThemeRegistryTestStringSectionRenderer('child', 'hero', 'child hero');

    themeRegistryTestRegisterTheme($registry, 'default', null, [$foundationHero]);
    themeRegistryTestRegisterTheme($registry, 'child', 'default', [$childHero]);

    $html = $registry->renderer('child')->render(new ThemePageData(
        title: 'Child',
        brand: new BrandProfileData,
        sections: [new HeroSectionData(heading: 'Heading')],
    ));

    expect($registry->sectionRenderer('child', 'hero'))->toBe($childHero)
        ->and($html)->toContain('child hero')
        ->and($html)->not->toContain('foundation hero');
});

it('uses the foundation parent renderer when a child omits a section', function (): void {
    $registry = new ThemeRegistry;
    app()->instance(ThemeRegistry::class, $registry);

    $foundationHero = new ThemeRegistryTestStringSectionRenderer('default', 'hero', 'foundation hero');

    themeRegistryTestRegisterTheme($registry, 'default', null, [$foundationHero]);
    themeRegistryTestRegisterTheme($registry, 'child', 'default', []);

    $html = $registry->renderer('child')->render(new ThemePageData(
        title: 'Child',
        brand: new BrandProfileData,
        sections: [new HeroSectionData(heading: 'Heading')],
    ));

    expect($registry->sectionRenderer('child', 'hero'))->toBe($foundationHero)
        ->and($html)->toContain('foundation hero');
});

it('returns null from the registry and fails loudly through rendering for missing sections', function (): void {
    $registry = new ThemeRegistry;
    app()->instance(ThemeRegistry::class, $registry);

    themeRegistryTestRegisterTheme($registry, 'default', null, []);
    themeRegistryTestRegisterTheme($registry, 'child', 'default', []);

    expect($registry->sectionRenderer('child', 'hero'))->toBeNull()
        ->and(fn (): string => $registry->renderer('child')->render(new ThemePageData(
            title: 'Child',
            brand: new BrandProfileData,
            sections: [new HeroSectionData(heading: 'Heading')],
        )))->toThrow(SectionRendererNotFoundException::class);
});

it('stops cyclic theme inheritance while resolving section renderers', function (): void {
    $registry = new ThemeRegistry;
    app()->instance(ThemeRegistry::class, $registry);

    themeRegistryTestRegisterTheme($registry, 'alpha', 'beta', []);
    themeRegistryTestRegisterTheme($registry, 'beta', 'alpha', []);

    expect($registry->sectionRenderer('alpha', 'hero'))->toBeNull();
});

/**
 * @param  array<int, SectionRenderer>  $sectionRenderers
 */
function themeRegistryTestRegisterTheme(
    ThemeRegistry $registry,
    string $themeKey,
    ?string $extends,
    array $sectionRenderers,
): void {
    $rendererMap = collect($sectionRenderers)
        ->mapWithKeys(fn (SectionRenderer $renderer): array => [$renderer->sectionKey() => $renderer])
        ->all();

    $registry->register(
        new ThemeDefinitionData(
            key: $themeKey,
            name: ucfirst($themeKey),
            description: 'Test theme',
            package: 'vendor/' . $themeKey,
            previewImage: '',
            tags: [],
            bestFit: [],
            includedSections: [],
            presets: [],
            extends: $extends,
        ),
        new BladeThemeRenderer($themeKey, 'missing-layout', $rendererMap),
        $sectionRenderers,
    );
}
