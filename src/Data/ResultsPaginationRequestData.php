<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class ResultsPaginationRequestData extends Data
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 24,
    ) {}
}
