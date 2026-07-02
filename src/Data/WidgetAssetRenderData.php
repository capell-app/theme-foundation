<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\Core\Enums\ContentStructure;
use Spatie\LaravelData\Data;

final class WidgetAssetRenderData extends Data
{
    /**
     * @param  array<int|string, mixed>  $actions
     * @param  array<string, mixed>  $meta
     * @param  array<int|string, mixed>  $social
     * @param  array<int|string, mixed>  $tags
     */
    public function __construct(
        public readonly mixed $asset,
        public readonly mixed $image,
        public readonly mixed $linkedPage,
        public readonly mixed $translation,
        public readonly array $meta,
        public readonly string $alt,
        public readonly array $actions,
        public readonly ?string $accent,
        public readonly ?string $caption,
        public readonly ?string $content,
        public readonly ?ContentStructure $contentStructure,
        public readonly ?string $cropPreset,
        public readonly ?string $headingSize,
        public readonly ?string $headingWeight,
        public readonly ?string $icon,
        public readonly ?string $linkText,
        public readonly ?string $linkUrl,
        public readonly ?string $position,
        public readonly ?string $role,
        public readonly array $social,
        public readonly ?string $status,
        public readonly array $tags,
        public readonly ?string $textAlign,
        public readonly ?string $title,
    ) {}
}
