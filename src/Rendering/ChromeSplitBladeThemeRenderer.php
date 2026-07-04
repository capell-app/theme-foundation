<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Rendering;

use Capell\Core\ThemeStudio\Contracts\SectionRenderer;
use Capell\Core\ThemeStudio\Contracts\ThemeSection;
use Capell\Core\ThemeStudio\Data\ThemePageData;
use Capell\Core\ThemeStudio\Exceptions\SectionRendererNotFoundException;
use Capell\Core\ThemeStudio\Rendering\BladeThemeRenderer;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Throwable;

/**
 * A landmark-correct BladeThemeRenderer: renders ThemePageData's dedicated
 * $navigation/$footer separately from its $sections and passes all three to
 * the layout view as $chromeHeader/$mainContent/$chromeFooter, so a migrated
 * theme's page.blade.php can place <nav>/<footer> as siblings of <main>
 * instead of nesting them inside it. $content (the legacy concatenation of
 * every section, chrome included) is still passed for layouts that haven't
 * migrated their Blade yet.
 *
 * BladeThemeRenderer::renderSection()/resolveSectionRenderer() are private,
 * so this class reimplements them rather than overriding — an unmigrated
 * theme keeps using plain BladeThemeRenderer unaffected; only a theme that
 * explicitly registers this renderer opts into the landmark split.
 */
class ChromeSplitBladeThemeRenderer extends BladeThemeRenderer
{
    /**
     * @param  array<string, SectionRenderer>  $sectionRenderers
     */
    public function __construct(
        private readonly string $themeKey,
        private readonly string $layoutView,
        private readonly array $sectionRenderers,
    ) {
        parent::__construct($themeKey, $layoutView, $sectionRenderers);
    }

    public function render(ThemePageData $page): string
    {
        $chromeHeaderHtml = $page->navigation instanceof ThemeSection ? $this->renderChromeSplitSection($page->navigation) : '';
        $chromeFooterHtml = $page->footer instanceof ThemeSection ? $this->renderChromeSplitSection($page->footer) : '';
        $mainContentHtml = implode("\n", array_map(
            fn (ThemeSection $section): string => $this->renderChromeSplitSection($section),
            $page->sections,
        ));

        $content = implode("\n", array_filter([$chromeHeaderHtml, $mainContentHtml, $chromeFooterHtml], fn (string $html): bool => $html !== ''));

        if (! function_exists('view')) {
            return $content;
        }

        try {
            /** @var view-string $layoutView */
            $layoutView = $this->layoutView;

            return view($layoutView, [
                'brand' => $page->brand,
                'content' => $content,
                'chromeHeader' => $chromeHeaderHtml,
                'chromeFooter' => $chromeFooterHtml,
                'mainContent' => $mainContentHtml,
                'page' => $page,
                'themeKey' => $this->themeKey,
            ])->render();
        } catch (Throwable) {
            return $content;
        }
    }

    private function renderChromeSplitSection(ThemeSection $section): string
    {
        $renderer = $this->resolveChromeSplitSectionRenderer($section->key());

        if (! $renderer instanceof SectionRenderer && $section->fallbackKey() !== null) {
            $renderer = $this->resolveChromeSplitSectionRenderer($section->fallbackKey());
        }

        if (! $renderer instanceof SectionRenderer) {
            throw SectionRendererNotFoundException::forSection($this->themeKey, $section->key());
        }

        return $renderer->render($section);
    }

    private function resolveChromeSplitSectionRenderer(string $sectionKey): ?SectionRenderer
    {
        if (function_exists('app') && app()->bound(ThemeRegistry::class)) {
            try {
                return resolve(ThemeRegistry::class)->sectionRenderer($this->themeKey, $sectionKey);
            } catch (Throwable) {
                return $this->sectionRenderers[$sectionKey] ?? null;
            }
        }

        return $this->sectionRenderers[$sectionKey] ?? null;
    }
}
