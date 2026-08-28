<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\LayoutBuilder\Support\CapellLayoutManager;

use function Pest\Laravel\get;

require_once dirname(__DIR__, 4) . '/tests/Packages/Support/PublicOutputSafety.php';
require_once dirname(__DIR__, 4) . '/tests/Packages/Support/ThemeLayoutNativeSupport.php';

dataset('footer widget rendering seams', [
    'Foundation fallback chrome' => ['default'],
    'Agency legacy footer section' => ['agency'],
    'Folio Foundation fallback chrome' => ['folio'],
    'Grounds custom footer chrome' => ['grounds'],
    'Switchboard custom footer chrome' => ['switchboard'],
]);

it('renders a stored footer-area widget through each footer seam on a real public route', function (string $themeKey): void {
    $registry = resolve(ThemeRegistry::class);
    $registry->reset();

    [$pageUrl, $pageTitle] = layoutNativeThemeCreatePage(
        $themeKey,
        'Footer Area Render',
    );

    $homepage = Page::query()
        ->where('meta->theme_demo->theme_key', $themeKey)
        ->where('meta->theme_demo->surface', 'homepage')
        ->firstOrFail();
    $homepage->loadMissing('layout');

    $layout = $homepage->layout;

    throw_unless($layout instanceof Layout, RuntimeException::class, 'Expected the Foundation demo homepage to have a layout.');

    $containers = $layout->containers;

    throw_unless(is_array($containers), RuntimeException::class, 'Expected the Foundation demo layout to have containers.');

    $main = $containers['main'] ?? null;
    throw_unless(is_array($main), RuntimeException::class, 'Expected the Foundation demo layout to have a main container.');

    $mainWidgets = $main['widgets'] ?? [];
    throw_unless(is_array($mainWidgets), RuntimeException::class, 'Expected the Foundation main container widgets to be an array.');

    $main['widgets'] = array_values(array_filter(
        $mainWidgets,
        static fn (mixed $widget): bool => ! is_array($widget) || ($widget['widget_key'] ?? null) !== 'page-content',
    ));
    $containers['main'] = $main;
    $containers['footer'] = [
        'meta' => [
            'area' => 'footer',
            'colspan' => 12,
        ],
        'widgets' => [
            [
                'widget_key' => 'page-content',
                'occurrence' => 1,
            ],
        ],
    ];

    $layout->forceFill(['containers' => $containers])->save();

    $theme = Theme::query()->where('key', $themeKey)->firstOrFail();
    $themeMeta = is_array($theme->meta) ? $theme->meta : [];
    unset($themeMeta['footer']);
    $themeMeta['header'] = false;
    $theme->forceFill(['meta' => $themeMeta])->save();

    CapellLayoutManager::clearContainerWidgets();

    $response = get($pageUrl->full_url);

    $response->assertOk();

    $html = $response->getContent();
    $footerContainerOffset = strpos($html, 'id="layout-container-footer"');

    // Themes may emit `<footer>` page furniture inside content (folio spreads do),
    // so anchor on the footer element that actually wraps the layout container.
    $footerOffset = is_int($footerContainerOffset)
        ? strrpos(substr($html, 0, $footerContainerOffset), '<footer')
        : false;
    $footerEndOffset = is_int($footerOffset) ? strpos($html, '</footer>', $footerOffset) : false;

    if (! is_int($footerOffset) || ! is_int($footerContainerOffset) || ! is_int($footerEndOffset)) {
        throw new RuntimeException('Expected the rendered footer offsets to be integers.');
    }

    expect($html)
        ->toContain('<footer')
        ->toContain('id="layout-container-footer"')
        ->toContain(e($pageTitle))
        ->and($footerContainerOffset)->toBeGreaterThan($footerOffset)
        ->and($footerContainerOffset)->toBeLessThan($footerEndOffset);

    assertCapellPublicOutputIsSafe($response, "{$themeKey} footer-area public HTML");

    CapellLayoutManager::clearContainerWidgets();
    CapellCore::clearPackages();
    $registry->reset();
})->with('footer widget rendering seams');
