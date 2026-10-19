<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class ResultsPaginationData extends Data
{
    /** @param list<ResultsPaginationLinkData> $links */
    public function __construct(
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $lastPage,
        public readonly array $links = [],
    ) {}
}
