<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\Core\Support\Security\PublicHtmlSanitizer;
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

    /**
     * Author-supplied rich text, sanitised for anonymous public output.
     */
    public function safeContentHtml(): string
    {
        if ($this->content === null || trim($this->content) === '') {
            return '';
        }

        return resolve(PublicHtmlSanitizer::class)->sanitize($this->content);
    }
}
