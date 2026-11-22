<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

/**
 * Resolves demo images recorded in a package's demo-assets.json to the
 * published, same-origin URL. A slot resolves only when its manifest entry
 * exists and the file ships with the package.
 *
 * Shared pools ship with Foundation. A pool only one theme uses ships with
 * that theme instead, so Foundation's distribution archive does not carry
 * every theme's photography; it publishes to the same URL either way.
 */
final class SelfHostedDemoMedia
{
    public const string PUBLIC_PATH = 'vendor/capell-theme-foundation/demo-media';

    private const string POOL_SLOT = '#^pool/([a-z-]+)/([a-z]+)-(\d+)$#';

    /** @var array<string, array<string, string>> */
    private static array $manifests = [];

    /** @var array<string, array<string, string>> */
    private static array $poolSources = [];

    private static ?string $packagesDirectory = null;

    public static function poolUrl(string $pool, string $group, int $index): ?string
    {
        $id = sprintf('pool/%s/%s-%d', $pool, $group, $index);

        return isset(self::poolSources()[$id]) ? self::url(substr($id, strlen('pool/')) . '.webp') : null;
    }

    /**
     * Recorded pool images as URLs, by pool then group, in slot order.
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function pools(): array
    {
        /** @var array<string, array<string, array<int, string>>> $indexed */
        $indexed = [];

        foreach (array_keys(self::poolSources()) as $id) {
            if (preg_match(self::POOL_SLOT, $id, $match) !== 1) {
                continue;
            }

            $indexed[$match[1]][$match[2]][(int) $match[3]] = self::url(substr($id, strlen('pool/')) . '.webp');
        }

        ksort($indexed);
        $pools = [];

        foreach ($indexed as $pool => $groups) {
            foreach ($groups as $group => $urls) {
                ksort($urls);
                $pools[$pool][$group] = array_values($urls);
            }
        }

        return $pools;
    }

    public static function firstScreenUrl(string $themeKey): ?string
    {
        $file = self::recordedFile(dirname(self::foundationDirectory()) . '/theme-' . $themeKey, 'first-screen/' . $themeKey);

        return $file === null ? null : self::url('first-screen/' . $themeKey . '.webp');
    }

    /**
     * A theme's own recorded image other than its first screen, such as the
     * Events speaker portraits.
     */
    public static function themeImageUrl(string $themeKey, string $id): ?string
    {
        $file = self::recordedFile(dirname(self::foundationDirectory()) . '/theme-' . $themeKey, $id);

        return $file === null ? null : self::url($themeKey . '/' . basename($file));
    }

    /**
     * Points resolution at another packages directory; null restores the installed one.
     */
    public static function usePackagesDirectory(?string $directory): void
    {
        self::$packagesDirectory = $directory;
        self::$manifests = [];
        self::$poolSources = [];
    }

    /**
     * Recorded pool images that installed themes ship themselves, keyed by
     * source file, valued by the path under the published demo-media
     * directory. Only recorded slots publish, and a slot Foundation already
     * records stays Foundation's, so a theme cannot overwrite another image.
     *
     * @return array<string, string>
     */
    public static function themePoolFiles(): array
    {
        $foundation = self::foundationDirectory() . '/';
        $files = [];

        foreach (self::poolSources() as $id => $source) {
            if (! str_starts_with($source, $foundation)) {
                $files[$source] = substr($id, strlen('pool/')) . '.webp';
            }
        }

        return $files;
    }

    public static function foundationDirectory(): string
    {
        return (self::$packagesDirectory ?? dirname(__DIR__, 4)) . '/theme-foundation';
    }

    /**
     * Recorded pool slots that ship, Foundation's first, then each installed
     * theme's. A slot's file must sit at the path its ID names, so a theme's
     * pool publishes to the same URL as it would from Foundation and cannot
     * overwrite a first screen or another theme's image.
     *
     * @return array<string, string> slot ID => absolute source file
     */
    private static function poolSources(): array
    {
        $foundation = self::foundationDirectory();

        if (isset(self::$poolSources[$foundation])) {
            return self::$poolSources[$foundation];
        }

        $themes = array_filter(
            glob(dirname($foundation) . '/theme-*', GLOB_ONLYDIR) ?: [],
            static fn (string $directory): bool => $directory !== $foundation,
        );
        $sources = [];

        foreach ([$foundation, ...$themes] as $directory) {
            foreach (self::$manifests[$directory] ??= self::read($directory) as $id => $file) {
                if (isset($sources[$id])
                    || preg_match(self::POOL_SLOT, $id) !== 1
                    || $file !== 'publishes/demo-media/' . substr($id, strlen('pool/')) . '.webp'
                    || ! is_file($directory . '/' . $file)) {
                    continue;
                }

                $sources[$id] = $directory . '/' . $file;
            }
        }

        return self::$poolSources[$foundation] = $sources;
    }

    private static function recordedFile(string $packageDirectory, string $id): ?string
    {
        $files = self::$manifests[$packageDirectory] ??= self::read($packageDirectory);
        $file = $files[$id] ?? null;

        return $file !== null && str_starts_with($file, 'publishes/demo-media/') && is_file($packageDirectory . '/' . $file) ? $file : null;
    }

    /**
     * @return array<string, string>
     */
    private static function read(string $packageDirectory): array
    {
        $path = $packageDirectory . '/demo-assets.json';
        $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $files = [];

        foreach (is_array($json) && is_array($json['assets'] ?? null) ? $json['assets'] : [] as $asset) {
            if (is_array($asset) && is_string($asset['id'] ?? null) && is_string($asset['file'] ?? null)) {
                $files[$asset['id']] = $asset['file'];
            }
        }

        return $files;
    }

    /**
     * Root-relative, so demo pages load images from whichever domain serves
     * them.
     */
    private static function url(string $relative): string
    {
        $assetUrl = config('app.asset_url');
        $prefix = is_string($assetUrl) ? parse_url($assetUrl, PHP_URL_PATH) : null;
        $prefix = is_string($prefix) ? trim($prefix, '/') : '';

        return ($prefix === '' ? '' : '/' . $prefix) . '/' . self::PUBLIC_PATH . '/' . $relative;
    }
}
