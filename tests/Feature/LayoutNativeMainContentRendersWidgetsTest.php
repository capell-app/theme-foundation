<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Socials\Data\PreparedSocialSiteData;
use Capell\Socials\Support\SocialsFrontendRuntimeManifestContributor;
use Capell\ThemeLiquidGlass\LiquidGlassThemeServiceProvider;
use Livewire\Blaze\Blaze;

use function Pest\Laravel\get;

require_once __DIR__ . '/../../../../tests/Packages/Support/ThemeLayoutNativeSupport.php';

// Class-backed layout and widget views must render content through Laravel,
// while Blaze remains enabled for eligible anonymous components. Registering
// those views as Blaze functions silently drops content; disabling Blaze during
// a nested render instead pollutes the cache used by later enabled renders.

it('renders real layout-builder widget content in the main landmark for a layout-native theme', function (string $themeKey, string $firstSection, string $secondSection): void {
    expect(Blaze::isEnabled())->toBeTrue();

    $registry = resolve(ThemeRegistry::class);
    $registry->reset();

    [$pageUrl, $pageTitle] = layoutNativeThemeCreatePage($themeKey, 'Main Content Regression');

    resolve(RenderHookRegistry::class)->registerCallable(
        location: RenderHookLocation::AfterContent,
        extension: static fn (): string => '<aside data-after-content-hook="visible">Recovery suggestions</aside>',
        scenario: 'frontend-main-layout',
        target: 'capell::layout.main',
    );

    expect($registry->has($themeKey))->toBeTrue();

    $response = get($pageUrl->full_url);

    $response->assertOk();
    expect(Blaze::isEnabled())->toBeTrue();

    $preparedSocials = Frontend::getFrontendData(SocialsFrontendRuntimeManifestContributor::RENDER_DATA_KEY);

    expect($preparedSocials)
        ->toBeInstanceOf(PreparedSocialSiteData::class)
        ->and(serialize($preparedSocials))->not->toContain('SocialNetworkRegistry');

    $html = $response->getContent();

    if ($html === false) {
        throw new RuntimeException('Expected the response to contain HTML.');
    }

    expect($html)->toBeString();

    if ($html === '') {
        throw new RuntimeException('Expected the layout-native page to render HTML.');
    }

    expect($html)
        ->toContain('id="main"')
        ->toContain('data-after-content-hook="visible"')
        ->toContain(e($pageTitle))
        ->toContain($firstSection)
        ->toContain($secondSection)
        ->not->toContain('data-capell-editor', 'signed-editor', '$__blaze');

    get($pageUrl->full_url)->assertOk()
        ->assertSee($firstSection)
        ->assertSee($secondSection);
    expect(Blaze::isEnabled())->toBeTrue();

    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//main//h1'))->toHaveCount(1);

    $registry->reset();
    CapellCore::clearPackages();
})->with([
    'Foundation sections' => ['default', 'Find a useful field note', 'Notes from the field'],
    'Liquid Glass widgets' => [LiquidGlassThemeServiceProvider::THEME_KEY, 'Teams that ship on the glass', 'One glass system, three token-driven presets'],
]);
