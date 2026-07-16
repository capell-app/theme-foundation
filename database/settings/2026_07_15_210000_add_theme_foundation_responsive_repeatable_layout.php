<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $key = 'theme_foundation.responsive_repeatable_layout';

        if (! $this->migrator->exists($key)) {
            $this->migrator->add($key, 'desktop-grid-mobile-carousel');
        }
    }
};
