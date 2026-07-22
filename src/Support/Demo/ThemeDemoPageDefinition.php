<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;

final class ThemeDemoPageDefinition
{
    /**
     * @param  array<string, mixed>  $renderData
     * @param  array<string, array<string, mixed>>|null  $containers  Layout container payload, same JSON shape as `Layout::containers`. When present, the installer writes this onto the resolved `Layout` model instead of storing `render_data['sections']`.
     * @param  list<array{method: string, args?: array<array-key, mixed>}>|null  $widgets  Widget blueprints consumed via `Capell\LayoutBuilder\Support\Creator\WidgetCreator` before `$containers` is applied, so any `widget_key` referenced by `$containers` already exists. The optional `args` key is spread positionally into the named `WidgetCreator` method call — added so a single method can be reused to create multiple distinctly-keyed widget instances (e.g. one bespoke widget per demo surface) instead of requiring one no-arg `WidgetCreator` method per instance.
     */
    public function __construct(
        public readonly string $surface,
        public readonly string $name,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $content,
        public readonly array $renderData,
        public readonly PageTypeEnum $type = PageTypeEnum::Default,
        public readonly LayoutEnum $layout = LayoutEnum::Default,
        public readonly ?array $containers = null,
        public readonly ?array $widgets = null,
    ) {}

    public function hasContainers(): bool
    {
        return is_array($this->containers) && $this->containers !== [];
    }

    /**
     * @return list<array{widget_key: string, occurrence: int}>
     */
    public function mainContainerWidgets(): array
    {
        $widgets = $this->containers['main']['widgets'] ?? null;

        if (! is_array($widgets)) {
            return [];
        }

        $normalized = [];

        foreach ($widgets as $widget) {
            if (! is_array($widget)) {
                continue;
            }

            $widgetKey = $widget['widget_key'] ?? null;
            $occurrence = $widget['occurrence'] ?? null;

            if (is_string($widgetKey) && is_int($occurrence)) {
                $normalized[] = ['widget_key' => $widgetKey, 'occurrence' => $occurrence];
            }
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public function bespokeWidgetComponentKeys(): array
    {
        $componentKeys = [];

        foreach (array_slice($this->widgets ?? [], 1) as $widget) {
            $componentKey = $widget['args'][2] ?? null;

            if (is_string($componentKey)) {
                $componentKeys[] = $componentKey;
            }
        }

        return $componentKeys;
    }

    /**
     * Reuse an authored demo composition for an additional, explicitly named
     * capture surface while preserving its layout and widget boundaries.
     *
     * @param  array<string, mixed>  $renderDataOverrides
     */
    public function forSurface(
        string $surface,
        string $name,
        string $title,
        string $slug,
        array $renderDataOverrides = [],
        ?LayoutEnum $layout = null,
    ): self {
        return new self(
            surface: $surface,
            name: $name,
            title: $title,
            slug: $slug,
            content: $this->content,
            renderData: array_replace($this->renderData, $renderDataOverrides),
            type: $this->type,
            layout: $layout ?? $this->layout,
            containers: $this->containers,
            widgets: $this->widgets,
        );
    }

    /**
     * The ordered section payloads this surface seeds, each normalised to a
     * string-keyed map so callers get a typed list instead of raw `mixed`.
     *
     * @return list<array<string, mixed>>
     */
    public function sections(): array
    {
        $sections = $this->renderData['sections'] ?? [];

        if (! is_array($sections)) {
            return [];
        }

        $list = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $map = [];

            foreach ($section as $key => $value) {
                $map[(string) $key] = $value;
            }

            $list[] = $map;
        }

        return $list;
    }

    /**
     * The ordered list of section `type` keys, coerced to strings.
     *
     * @return list<string>
     */
    public function sectionTypes(): array
    {
        $types = [];

        foreach ($this->sections() as $section) {
            $type = $section['type'] ?? null;
            $types[] = is_string($type) ? $type : '';
        }

        return $types;
    }
}
