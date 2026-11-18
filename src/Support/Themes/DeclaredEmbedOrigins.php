<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Themes;

use Capell\Core\Models\Theme;
use Capell\Core\ThemeStudio\Contracts\ThemeRuntimeSettings;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;
use Capell\Frontend\Contracts\FrontendContextReader;
use Illuminate\Contracts\Container\Container;

/**
 * Reads the third-party origins the active theme declares for each embed
 * widget in `ThemeDefinitionData->frontend['embedOrigins']`, keyed by widget:
 * `['map-embed' => ['https://www.openstreetmap.org']]`. An embed whose source
 * is not on its widget's list never renders, so a page only requests its own
 * origin plus origins the theme's manifest declares.
 */
final class DeclaredEmbedOrigins
{
    /** @var array<string, list<string>>|null */
    private ?array $origins = null;

    public function __construct(private readonly Container $container) {}

    public static function originOf(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || ! isset($parts['host']) || $parts['host'] === '') {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return 'https://' . strtolower($parts['host']) . $port;
    }

    /**
     * @return list<string>
     */
    public function for(string $widget): array
    {
        return $this->origins()[$widget] ?? [];
    }

    public function permits(string $widget, string $url): bool
    {
        $origin = self::originOf($url);

        return $origin !== null && in_array($origin, $this->for($widget), true);
    }

    /**
     * @return array<string, list<string>>
     */
    private function origins(): array
    {
        if ($this->origins !== null) {
            return $this->origins;
        }

        $this->origins = [];

        if (! $this->container->bound(ThemeRuntimeSettings::class)) {
            return $this->origins;
        }

        $theme = $this->container->bound(FrontendContextReader::class)
            ? $this->container->make(FrontendContextReader::class)->theme()
            : null;
        $themeKey = $theme instanceof Theme ? $theme->key : $this->container->make(ThemeRuntimeSettings::class)->activeTheme();
        $registry = $this->container->make(ThemeRegistry::class);

        if (! is_string($themeKey) || ! $registry->has($themeKey)) {
            return $this->origins;
        }

        $declared = $registry->definition($themeKey)->frontend['embedOrigins'] ?? [];

        if (! is_array($declared)) {
            return $this->origins;
        }

        foreach ($declared as $widget => $origins) {
            if (! is_string($widget) || ! is_array($origins)) {
                continue;
            }

            $this->origins[$widget] = array_values(array_filter(
                array_map(static fn (mixed $origin): ?string => is_string($origin) ? self::originOf($origin) : null, $origins),
                is_string(...),
            ));
        }

        return $this->origins;
    }
}
