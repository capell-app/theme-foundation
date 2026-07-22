<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Spatie\LaravelData\Data;

final class ResultsListingData extends Data
{
    /**
     * @param  list<array{title: string, summary: string|null, url: string, type: string|null, image: string|null, publishedDate: string|null}>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?string $archiveUrl = null,
    ) {}
}
