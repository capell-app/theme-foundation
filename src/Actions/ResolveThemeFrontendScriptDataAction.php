<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\FoundationTheme\Data\ThemeFrontendScriptData;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static ThemeFrontendScriptData run(string $themeKey, string $packageName, string $entry, string $publicDirectory)
 */
final class ResolveThemeFrontendScriptDataAction
{
    use AsFake;
    use AsObject;

    public function handle(
        string $themeKey,
        string $packageName,
        string $entry,
        string $publicDirectory,
    ): ThemeFrontendScriptData {
        $this->assertIdentifier($themeKey, '/\A[a-z][a-z0-9-]{0,63}\z/', 'Theme key');
        $this->assertIdentifier($packageName, '/\A[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\z/', 'Package name');
        $this->assertSafeRelativePath($entry, 'Manifest entry');
        $this->assertSafeRelativePath($publicDirectory, 'Public directory');

        if (! str_ends_with($entry, '.js')) {
            throw new InvalidArgumentException('Theme frontend entries must use the .js extension.');
        }

        return new ThemeFrontendScriptData(
            themeKey: $themeKey,
            handle: $packageName . ':frontend-runtime',
            packageName: $packageName,
            entry: $entry,
            publicDirectory: trim($publicDirectory, '/'),
        );
    }

    private function assertIdentifier(string $value, string $pattern, string $label): void
    {
        if (preg_match($pattern, $value) !== 1) {
            throw new InvalidArgumentException(sprintf('%s [%s] is invalid.', $label, $value));
        }
    }

    private function assertSafeRelativePath(string $path, string $label): void
    {
        if (
            $path === ''
            || str_starts_with($path, '/')
            || str_contains($path, '\\')
            || preg_match('#(^|/)\.\.(/|$)#', $path) === 1
            || preg_match('/\A[a-z][a-z0-9+.-]*:/i', $path) === 1
        ) {
            throw new InvalidArgumentException(sprintf('%s [%s] must be a safe relative path.', $label, $path));
        }
    }
}
