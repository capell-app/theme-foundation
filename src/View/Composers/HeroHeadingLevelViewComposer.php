<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Composers;

use Capell\FoundationTheme\Actions\ResolveHeroHeadingLevelAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * Hydrates `headingLevel` on every primary hero view before it renders, so the
 * hero template prints `{{ $headingLevel }}` and never decides, or claims, the
 * page's h1 in Blade. A view that already received a level from a delegating
 * parent keeps it.
 */
final class HeroHeadingLevelViewComposer
{
    /** Last view-name segment of every primary hero, lede and spotlight view. */
    public const string VIEW_BASENAME_PATTERN = '/^(?:[a-z]+-)*hero(?:--[a-z-]+)?$|^front-page-lede$/';

    /** @var list<string> */
    private const array HERO_BANNER_VIEWS = [
        'capell::components.widget.modern.hero-banner',
        'capell-theme-foundation::components.widget.modern.hero-banner',
        'capell::widget.modern.hero-banner',
        'capell-theme-foundation::widget.modern.hero-banner',
    ];

    public static function register(): void
    {
        ViewFacade::composer(['*hero*', '*front-page-lede*'], self::class);
    }

    public function compose(View $view): void
    {
        $data = $view->getData();

        if (isset($data['headingLevel'])) {
            return;
        }

        $name = $view->name();

        if (in_array($name, self::HERO_BANNER_VIEWS, true)) {
            $widget = $data['widget'] ?? null;
            $title = $data['title'] ?? (is_object($widget) ? data_get($widget, 'translation.title') : null);
            $configured = is_object($widget) && method_exists($widget, 'getMeta')
                ? $widget->getMeta('heading_tag', '')
                : null;

            $view->with('headingLevel', ResolveHeroHeadingLevelAction::run(
                is_string($configured) ? $configured : null,
                is_string($title) && $title !== '',
            ));

            return;
        }

        // Only theme views render $headingLevel; a package hero widget must not claim the page h1.
        if (! str_starts_with($name, 'capell-theme-')) {
            return;
        }

        $segments = preg_split('/[.:]+/', $name) ?: [];
        $basename = (string) end($segments);

        if (preg_match(self::VIEW_BASENAME_PATTERN, $basename) !== 1) {
            return;
        }

        $view->with('headingLevel', ResolveHeroHeadingLevelAction::run());
    }
}
