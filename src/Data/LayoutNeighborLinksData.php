<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\Core\Contracts\Pageable;
use Spatie\LaravelData\Data;

final class LayoutNeighborLinksData extends Data
{
    public function __construct(
        public ?Pageable $previousPage,
        public ?Pageable $nextPage,
    ) {}

    public function shouldRender(): bool
    {
        return $this->previousPage instanceof Pageable || $this->nextPage instanceof Pageable;
    }
}
