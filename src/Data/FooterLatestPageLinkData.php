<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class FooterLatestPageLinkData extends Data
{
    public function __construct(
        public readonly mixed $page,
        public readonly string $url,
    ) {}
}
