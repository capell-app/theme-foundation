<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Rendering;

use Capell\Core\ThemeStudio\Contracts\SectionRenderer;
use Capell\Core\ThemeStudio\Contracts\ThemeSection;
use Throwable;

class VariantViewSectionRenderer implements SectionRenderer
{
    /**
     * @param  array<string, string>  $variantViews
     * @param  array<string, mixed>  $extraViewData
     */
    public function __construct(
        private readonly string $themeKey,
        private readonly string $sectionKey,
        private readonly string $baseView,
        private readonly array $variantViews = [],
        private readonly bool $failLoudly = false,
        private readonly array $extraViewData = [],
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
        if (! function_exists('view')) {
            return $this->fallbackHtml($section);
        }

        $viewData = $section->toViewData();
        $variant = $viewData['variant'] ?? null;
        $view = is_string($variant) && isset($this->variantViews[$variant])
            ? $this->variantViews[$variant]
            : $this->baseView;

        try {
            /** @var view-string $view */
            return view($view, [
                ...$viewData,
                ...$this->extraViewData,
            ])->render();
        } catch (Throwable $throwable) {
            throw_if($this->failLoudly, $throwable);

            return $this->fallbackHtml($section);
        }
    }

    private function fallbackHtml(ThemeSection $section): string
    {
        return '<section data-theme="' . $this->escape($this->themeKey) . '" data-section="' . $this->escape($section->key()) . '"></section>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
