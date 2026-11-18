<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

final class ThemeDemoMedia
{
    /** @var array<string, string> */
    private const array MEDIA_POOL_BY_THEME = [
        'agency' => 'agency',
        'awards' => 'corporate',
        'blog' => 'knowledge',
        'bistro' => 'bistro',
        'brutalist' => 'corporate',
        'business' => 'local-services',
        'catalogue' => 'portfolio',
        'curated' => 'portfolio',
        'directory' => 'agency',
        'editorial' => 'knowledge',
        'events' => 'nonprofit',
        'knowledge' => 'knowledge',
        'liquid-glass' => 'saas',
        'magazine' => 'knowledge',
        'minimalist' => 'portfolio',
        'onepage' => 'agency',
        'platform' => 'saas',
        'portfolio' => 'portfolio',
        'saas' => 'saas',
        'showreel' => 'portfolio',
        'submissions' => 'commerce',
    ];

    /**
     * Pools that borrow further workplace and studio photography from sibling
     * pools so a long homepage can give every section its own images.
     *
     * @var array<string, list<string>>
     */
    private const array POOL_EXTENSIONS = [
        'agency' => ['portfolio', 'saas', 'corporate', 'default'],
        'portfolio' => ['agency', 'saas', 'corporate', 'default'],
        'saas' => ['agency', 'corporate', 'portfolio', 'default'],
    ];

    /**
     * The theme's own photography followed by borrowed photography, distinct
     * and in a stable order.
     *
     * @return list<string>
     */
    public static function distinctPool(string $themeKey): array
    {
        $normalizedThemeKey = strtolower(trim($themeKey));
        $mediaPoolKey = self::MEDIA_POOL_BY_THEME[$normalizedThemeKey] ?? $normalizedThemeKey;
        $catalogue = self::catalogue();
        $pool = self::flatten(self::withFirstScreen($catalogue[$mediaPoolKey] ?? $catalogue['default'], $normalizedThemeKey));

        foreach (self::POOL_EXTENSIONS[$mediaPoolKey] ?? [] as $extension) {
            $pool = array_merge($pool, self::flatten($catalogue[$extension] ?? []));
        }

        return self::uniquePhotographs($pool);
    }

    /**
     * The grouped media shape with every group drawn from one slice of the
     * distinct pool, starting at `$offset`, so each homepage section can take its own slice and no
     * two sections show the same photograph.
     *
     * @return array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}
     */
    public static function window(string $themeKey, int $offset, int $size = 3): array
    {
        $pool = self::distinctPool($themeKey);
        $count = count($pool);
        $slice = [];

        for ($step = 0; $step < $size; $step++) {
            $slice[] = $pool[($offset + $step) % $count];
        }

        $slice = array_values(array_unique($slice));

        return [
            'hero' => $slice,
            'listing' => $slice,
            'detail' => $slice,
            'proof' => $slice,
            'contact' => $slice,
            'cta' => $slice,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function forTheme(string $themeKey): array
    {
        return array_values(array_merge(...array_values(self::groupedForTheme($themeKey))));
    }

    /**
     * @return array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}
     */
    public static function groupedForTheme(string $themeKey): array
    {
        $normalizedThemeKey = strtolower(trim($themeKey));
        $catalogue = self::catalogue();
        $mediaPoolKey = self::MEDIA_POOL_BY_THEME[$normalizedThemeKey] ?? $normalizedThemeKey;

        return self::withMinimumPreviewMedia(
            self::withFirstScreen($catalogue[$mediaPoolKey] ?? $catalogue['default'], $normalizedThemeKey),
        );
    }

    /**
     * Opens the theme on its own self-hosted first-screen image when one is
     * recorded, so no two themes share a hero.
     *
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $groups
     * @return array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}
     */
    private static function withFirstScreen(array $groups, string $themeKey): array
    {
        $firstScreen = SelfHostedDemoMedia::firstScreenUrl($themeKey);

        if ($firstScreen !== null) {
            $groups['hero'] = [$firstScreen];
        }

        return $groups;
    }

    /**
     * @param  array<string, list<string>>  $groups
     * @return list<string>
     */
    private static function flatten(array $groups): array
    {
        return self::uniquePhotographs(array_merge(...array_values($groups)));
    }

    /**
     * @param  list<string>  $urls
     * @return list<string>
     */
    private static function uniquePhotographs(array $urls): array
    {
        return array_values(array_unique($urls));
    }

    /**
     * @param  array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}  $media
     * @return array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}
     */
    private static function withMinimumPreviewMedia(array $media): array
    {
        $pool = array_values(array_unique(array_merge(
            $media['hero'],
            $media['listing'],
            $media['detail'],
            $media['proof'],
            $media['contact'],
            $media['cta'],
        )));

        $media['listing'] = array_slice(array_values(array_unique(array_merge($media['listing'], $pool))), 0, 3);
        $media['proof'] = array_slice(array_values(array_unique(array_merge($media['proof'], $pool))), 0, 3);

        return $media;
    }

    /**
     * Every pool's self-hosted images, grouped as the demo pages use them. A
     * pool opens on its first listing image; themes with their own first
     * screen replace it in withFirstScreen().
     *
     * @return array<string, array{hero: list<string>, listing: list<string>, detail: list<string>, proof: list<string>, contact: list<string>, cta: list<string>}>
     */
    private static function catalogue(): array
    {
        $catalogue = [];

        foreach (SelfHostedDemoMedia::pools() as $pool => $groups) {
            $listing = $groups['listing'] ?? [];
            $catalogue[$pool] = [
                'hero' => array_slice($listing, 0, 1),
                'listing' => $listing,
                'detail' => $groups['detail'] ?? [],
                'proof' => $groups['proof'] ?? [],
                'contact' => $groups['contact'] ?? [],
                'cta' => $groups['cta'] ?? [],
            ];
        }

        return $catalogue;
    }
}
