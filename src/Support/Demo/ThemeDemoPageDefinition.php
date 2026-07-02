<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PageTypeEnum;

final class ThemeDemoPageDefinition
{
    /**
     * @param  array<string, mixed>  $renderData
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
    ) {}

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
