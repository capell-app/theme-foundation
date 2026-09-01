<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Demo;

trait InteractsWithThemeDemoProfile
{
    private function profileOption(): ?string
    {
        $profile = $this->option('profile');

        return is_string($profile) && trim($profile) !== '' ? trim($profile) : null;
    }
}
