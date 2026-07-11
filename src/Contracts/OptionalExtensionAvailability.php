<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Contracts;

interface OptionalExtensionAvailability
{
    /**
     * @param  list<class-string>  $requiredClasses
     */
    public function packageAvailable(string $packageName, array $requiredClasses = []): bool;

    public function livewireComponentAvailable(string $packageName, string $componentName): bool;
}
