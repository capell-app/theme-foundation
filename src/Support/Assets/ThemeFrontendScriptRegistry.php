<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Assets;

use Capell\FoundationTheme\Data\ThemeFrontendScriptData;
use InvalidArgumentException;

final class ThemeFrontendScriptRegistry
{
    /** @var array<string, ThemeFrontendScriptData> */
    private array $scripts = [];

    public function register(ThemeFrontendScriptData $script): void
    {
        if (array_key_exists($script->themeKey, $this->scripts)) {
            throw new InvalidArgumentException(sprintf('Theme frontend script [%s] is already registered.', $script->themeKey));
        }

        $this->scripts[$script->themeKey] = $script;
    }

    public function forTheme(string $themeKey): ?ThemeFrontendScriptData
    {
        return $this->scripts[$themeKey] ?? null;
    }

    /** @return array<string, ThemeFrontendScriptData> */
    public function all(): array
    {
        return $this->scripts;
    }
}
