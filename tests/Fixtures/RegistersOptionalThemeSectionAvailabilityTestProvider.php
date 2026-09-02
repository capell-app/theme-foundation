<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Fixtures;

use Capell\FoundationTheme\Support\Providers\RegistersOptionalThemeSectionAvailability;
use Illuminate\Support\ServiceProvider;

final class RegistersOptionalThemeSectionAvailabilityTestProvider extends ServiceProvider
{
    use RegistersOptionalThemeSectionAvailability;

    /**
     * @param  array<string, string>  $packageBySection
     */
    public function registerAvailability(string $viewName, array $packageBySection): void
    {
        $this->registerOptionalThemeSectionAvailability($viewName, $packageBySection);
    }
}
