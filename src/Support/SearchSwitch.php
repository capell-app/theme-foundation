<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Bridges\PublicSearchSwitchBridge;

/**
 * Lets themes ask whether public search is switched on without depending on the Search package.
 * When Search is not installed the switch has no opinion and each theme's own package check decides.
 * Resolve it from the container; the Search package caches its answer for the request.
 */
final readonly class SearchSwitch
{
    public function enabled(): bool
    {
        if (! CapellCore::isPackageAvailable('capell-app/search')) {
            return true;
        }

        return PublicSearchSwitchBridge::enabled();
    }

    /** The public results page as a root-relative URL, following the Search package's configured path. */
    public function resultsUrl(): string
    {
        if (! CapellCore::isPackageAvailable('capell-app/search')) {
            return '/search';
        }

        return '/' . PublicSearchSwitchBridge::resultsPath();
    }

    /** True when a form would post to the Search package's results page while that page is switched off. */
    public function blocksAction(string $action): bool
    {
        if (! CapellCore::isPackageAvailable('capell-app/search')) {
            return false;
        }

        if ($this->enabled()) {
            return false;
        }

        return trim((string) parse_url($action, PHP_URL_PATH), '/') === PublicSearchSwitchBridge::resultsPath();
    }
}
