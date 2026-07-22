<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Providers;

use Capell\FoundationTheme\Actions\ResolveThemeOptionalSectionAvailabilityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

trait RegistersOptionalThemeSectionAvailability
{
    /**
     * @param  array<string, string>  $packageBySection
     */
    protected function registerOptionalThemeSectionAvailability(string $viewName, array $packageBySection): void
    {
        ViewFacade::composer(
            $viewName,
            static function (View $view) use ($packageBySection): void {
                $widget = $view->getData()['widget'] ?? null;
                $section = is_object($widget) && method_exists($widget, 'getAttribute')
                    ? (array) $widget->getAttribute('meta')
                    : [];
                $sectionType = is_string($section['type'] ?? null) ? $section['type'] : '';

                $view->with(
                    'optionalSectionAvailable',
                    ResolveThemeOptionalSectionAvailabilityAction::run($sectionType, $packageBySection),
                );
            },
        );
    }
}
