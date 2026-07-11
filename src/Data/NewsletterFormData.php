<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

final readonly class NewsletterFormData
{
    public function __construct(
        public string $action,
        public string $method,
        public string $source,
        public bool $wired,
    ) {}
}
