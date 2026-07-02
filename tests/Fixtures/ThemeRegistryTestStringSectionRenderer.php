<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\Core\ThemeStudio\Contracts\SectionRenderer;
use Capell\Core\ThemeStudio\Contracts\ThemeSection;

final class ThemeRegistryTestStringSectionRenderer implements SectionRenderer
{
    public function __construct(
        private readonly string $themeKey,
        private readonly string $sectionKey,
        private readonly string $html,
    ) {}

    public function themeKey(): string
    {
        return $this->themeKey;
    }

    public function sectionKey(): string
    {
        return $this->sectionKey;
    }

    public function render(ThemeSection $section): string
    {
        return '<section data-theme="' . $this->themeKey . '" data-section="' . $section->key() . '">' . $this->html . '</section>';
    }
}
