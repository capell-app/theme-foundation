<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class BannerImageRenderData extends Data
{
    public function __construct(
        public readonly mixed $backgroundImage,
        public readonly mixed $actions,
        public readonly bool $hasContent,
        public readonly string $imageRoundedClass,
    ) {}
}
