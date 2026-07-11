<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

final readonly class ThemeFormEmbedData
{
    /**
     * @param  array<string, mixed>  $widgetData
     */
    public function __construct(
        public int|string|null $handle,
        public string $componentName,
        public string $instanceId,
        public array $widgetData,
        public bool $available,
    ) {}
}
