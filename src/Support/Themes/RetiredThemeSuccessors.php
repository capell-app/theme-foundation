<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Themes;

/**
 * Themes withdrawn from sale and the theme each one was merged into.
 *
 * `capell:theme-migrate` only moves sites along these routes. Run it on every
 * environment that has sites on a retired theme before that theme's package is
 * removed: once the package is gone the retired theme can no longer render and
 * the migration has nothing to preview against.
 */
final class RetiredThemeSuccessors
{
    /** @var array<string, string> retired theme key => successor theme key */
    private const array SUCCESSORS = [
        'magazine' => 'editorial',
        'blog' => 'editorial',
        'broadsheet' => 'editorial',
        'folio' => 'editorial',
        'bistro' => 'business',
        'events' => 'business',
        'grounds' => 'business',
        'almanac' => 'business',
        'knowledge' => 'platform',
        'switchboard' => 'platform',
        'liquid-glass' => 'platform',
        'showreel' => 'portfolio',
        'submissions' => 'portfolio',
        'curated' => 'portfolio',
        'saas' => 'portfolio',
        'agency' => 'portfolio',
        'onepage' => 'portfolio',
        'paperdesk' => 'portfolio',
        'catalogue' => 'portfolio',
        'directory' => 'portfolio',
        'minimalist' => 'portfolio',
        'brutalist' => 'portfolio',
        'awards' => 'portfolio',
    ];

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return self::SUCCESSORS;
    }

    public function successorFor(string $retiredThemeKey): ?string
    {
        return self::SUCCESSORS[$retiredThemeKey] ?? null;
    }
}
