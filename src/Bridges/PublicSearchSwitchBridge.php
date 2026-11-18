<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Bridges;

use Capell\Core\Facades\CapellCore;
use Capell\Search\Support\PublicSearchSwitch;

/**
 * Reads the Search package's public-search switch. Search is optional, so callers reach this
 * class only after confirming `capell-app/search` is available.
 */
final class PublicSearchSwitchBridge
{
    public static function enabled(): bool
    {
        if (! CapellCore::isPackageAvailable('capell-app/search')) {
            return true;
        }

        return PublicSearchSwitch::enabled();
    }

    /** The path of the public results page, without a leading slash. */
    public static function resultsPath(): string
    {
        if (! CapellCore::isPackageAvailable('capell-app/search')) {
            return 'search';
        }

        return PublicSearchSwitch::resultsPath();
    }
}
