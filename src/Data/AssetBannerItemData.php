<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class AssetBannerItemData extends Data
{
    public function __construct(
        public readonly mixed $image,
        public readonly string $alt,
        public readonly ?string $title,
        public readonly ?string $content,
        public readonly ?string $url,
        public readonly ?string $linkText,
    ) {}
}
