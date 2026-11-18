<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

/**
 * Resolves demo images recorded in a package's demo-assets.json to the
 * published, same-origin URL. A slot resolves only when its manifest entry
 * exists and the file ships with the package.
 */
final class SelfHostedDemoMedia
{
    public const string PUBLIC_PATH = 'vendor/capell-theme-foundation/demo-media';

    /** @var array<string, array<string, string>> */
    private static array $manifests = [];

    private static ?string $packagesDirectory = null;

    public static function poolUrl(string $pool, string $group, int $index): ?string
    {
        $file = self::recordedFile(self::foundationDirectory(), sprintf('pool/%s/%s-%d', $pool, $group, $index));

        return $file === null ? null : self::url(substr($file, strlen('publishes/demo-media/')));
    }

    /**
     * Foundation's recorded pool images as URLs, by pool then group, in slot
     * order.
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function pools(): array
    {
        $directory = self::foundationDirectory();
        $files = self::$manifests[$directory] ??= self::read($directory);
        /** @var array<string, array<string, array<int, string>>> $indexed */
        $indexed = [];

        foreach ($files as $id => $file) {
            if (preg_match('#^pool/([a-z-]+)/([a-z]+)-(\d+)$#', $id, $match) !== 1 || ! is_file($directory . '/' . $file)) {
                continue;
            }

            $indexed[$match[1]][$match[2]][(int) $match[3]] = self::url(substr($file, strlen('publishes/demo-media/')));
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
    }

    public static function foundationDirectory(): string
    {
        return (self::$packagesDirectory ?? dirname(__DIR__, 4)) . '/theme-foundation';
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
