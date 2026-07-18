<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support;

use Capell\Core\Facades\CapellCore;
use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Livewire\LivewireManager;

final readonly class CapellOptionalExtensionAvailability implements OptionalExtensionAvailability
{
    public function __construct(private LivewireManager $livewire) {}

    public function packageAvailable(string $packageName, array $requiredClasses = []): bool
    {
        return CapellCore::isPackageAvailable($packageName)
            && array_all(
                $requiredClasses,
                static fn (string $requiredClass): bool => class_exists($requiredClass),
            );
    }

    public function livewireComponentAvailable(string $packageName, string $componentName): bool
    {
        return $this->packageAvailable($packageName)
            && $this->livewire->exists($componentName);
    }
}
