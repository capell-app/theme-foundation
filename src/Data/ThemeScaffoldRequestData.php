<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\FoundationTheme\Actions\GenerateThemeScaffoldAction;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

/**
 * Structured input for {@see GenerateThemeScaffoldAction}.
 *
 * Carries everything the generator needs to render the `make-theme` stub
 * templates for a brand-new theme package: the stable theme slug/key, the
 * human-facing display name, the commercial tier, the catalogue family, and
 * the base packages directory the scaffold should be written under (kept
 * overridable so tests can point it at a temporary directory instead of the
 * real monorepo `packages/` tree).
 */
final class ThemeScaffoldRequestData extends Data
{
    /**
     * @var list<string>
     */
    public const array ALLOWED_TIERS = ['free', 'premium'];

    public readonly string $themeSlug;

    public readonly string $displayName;

    public readonly string $tier;

    public readonly string $family;

    public readonly string $basePackagesPath;

    public function __construct(
        string $themeSlug,
        string $displayName,
        string $tier,
        string $family,
        string $basePackagesPath,
    ) {
        $normalizedThemeSlug = strtolower(trim($themeSlug));

        if ($normalizedThemeSlug === '' || preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $normalizedThemeSlug) !== 1) {
            throw new InvalidArgumentException('Theme slug must be lowercase kebab-case, e.g. "call-out".');
        }

        $normalizedDisplayName = trim($displayName);

        if ($normalizedDisplayName === '') {
            throw new InvalidArgumentException('Theme display name must not be empty.');
        }

        $normalizedTier = strtolower(trim($tier));

        if (! in_array($normalizedTier, self::ALLOWED_TIERS, true)) {
            throw new InvalidArgumentException('Theme tier must be one of: ' . implode(', ', self::ALLOWED_TIERS) . '.');
        }

        $normalizedFamily = trim($family);

        if ($normalizedFamily === '') {
            throw new InvalidArgumentException('Theme family must not be empty.');
        }

        $normalizedBasePackagesPath = rtrim(trim($basePackagesPath), '/');

        if ($normalizedBasePackagesPath === '') {
            throw new InvalidArgumentException('Base packages path must not be empty.');
        }

        $this->themeSlug = $normalizedThemeSlug;
        $this->displayName = $normalizedDisplayName;
        $this->tier = $normalizedTier;
        $this->family = $normalizedFamily;
        $this->basePackagesPath = $normalizedBasePackagesPath;
    }

    public function themeKey(): string
    {
        return $this->themeSlug;
    }

    public function packageDirectoryName(): string
    {
        return 'theme-' . $this->themeSlug;
    }

    public function packageDirectory(): string
    {
        return $this->basePackagesPath . '/' . $this->packageDirectoryName();
    }

    public function composerPackageName(): string
    {
        return 'capell-app/theme-' . $this->themeSlug;
    }

    /**
     * PascalCase class-name fragment derived from the kebab-case slug, e.g.
     * `call-out` -> `CallOut`.
     */
    public function studlyName(): string
    {
        $words = explode('-', $this->themeSlug);
        $capitalizedWords = array_map(
            static fn (string $word): string => ucfirst($word),
            $words,
        );

        return implode('', $capitalizedWords);
    }

    /**
     * Root PHP namespace for the generated package, mirroring the
     * `Capell\ThemeStudio\{StudlyName}` convention used by existing
     * layout-native theme packages (e.g. `Capell\ThemeStudio\NightShift`).
     */
    public function rootNamespace(): string
    {
        return 'Capell\\ThemeStudio\\' . $this->studlyName();
    }
}
