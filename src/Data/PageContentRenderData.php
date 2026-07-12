<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\Core\Enums\ContentStructure;
use Spatie\LaravelData\Data;

final class PageContentRenderData extends Data
{
    public function __construct(
        public readonly mixed $image,
        public readonly ?string $content,
        public readonly ?ContentStructure $contentStructure,
        public readonly bool $hasContent,
        public readonly bool $hasTitle,
        public readonly ?string $title,
    ) {}
}
