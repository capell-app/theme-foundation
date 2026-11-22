<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\Core\Contracts\PublicRenderData;
use Capell\FoundationTheme\Support\Render\FoundationImageLoadingContributor;
use Capell\Frontend\Data\PublicPageRenderData;

final readonly class FoundationImageLoadingData implements PublicRenderData
{
    public function __construct(public string $loading) {}

    /**
     * Core stores every contribution as a plain public object, so the typed
     * payload is rebuilt here; null when the value is missing or misshapen.
     */
    public static function fromRenderData(PublicPageRenderData $renderData): ?self
    {
        $payload = $renderData->extensionData(FoundationImageLoadingContributor::KEY);
        if ($payload instanceof self) {
            return $payload;
        }

        $loading = $payload === null ? null : (get_object_vars($payload)['loading'] ?? null);

        return in_array($loading, ['eager', 'lazy'], true) ? new self($loading) : null;
    }

    /** @return 'eager'|'lazy' */
    public function secondaryImageLoading(): string
    {
        return $this->loading === 'eager' ? 'eager' : 'lazy';
    }
}
