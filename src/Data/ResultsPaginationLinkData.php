<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class ResultsPaginationLinkData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly ?string $url,
        public readonly bool $current = false,
        public readonly ?string $relation = null,
    ) {}
}
