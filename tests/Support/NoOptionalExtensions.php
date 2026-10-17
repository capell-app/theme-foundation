<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Support;

use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Override;

// Availability is a separate boundary from installed package state; render fallback fixtures explicitly.
final readonly class NoOptionalExtensions implements OptionalExtensionAvailability
{
    /** @param list<class-string> $requiredClasses */
    #[Override]
    public function packageAvailable(string $packageName, array $requiredClasses = []): bool
    {
        return false;
    }

    #[Override]
    public function livewireComponentAvailable(string $packageName, string $componentName): bool
    {
        return false;
    }
}
