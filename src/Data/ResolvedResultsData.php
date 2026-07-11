<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class ResolvedResultsData extends Data
{
    /**
     * @param  list<array{title: string, summary: string|null, url: string, type: string|null}>  $items
     */
    public function __construct(
        public readonly string $heading,
        public readonly ?string $summary,
        public readonly array $items,
    ) {}
}
