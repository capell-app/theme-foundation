<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Composers;

use Capell\FoundationTheme\Support\SectionVariantViewResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * Hydrates a theme's `widget.section` dispatcher with the already-resolved
 * section meta and view name, so the Blade stays free of `@php` blocks and of
 * the per-theme `$variantViews` lookup maps this replaces.
 *
 * `sectionView` is a view name only — never the raw widget state — so nothing
 * the editor picked leaks into anonymous public output.
 */
final class SectionVariantViewComposer
{
    public static function register(string $viewName, string $viewNamespace): void
    {
        ViewFacade::composer(
            $viewName,
            static function (View $view) use ($viewNamespace): void {
                $widget = $view->getData()['widget'] ?? null;
                $section = is_object($widget) && method_exists($widget, 'getAttribute')
                    ? (array) $widget->getAttribute('meta')
                    : [];

                $view->with('sectionMeta', $section);
                $view->with('sectionView', SectionVariantViewResolver::resolve($viewNamespace, $section));
            },
        );
    }
}
