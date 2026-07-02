<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('theme_foundation.heading_scale')) {
            $this->migrator->add('theme_foundation.heading_scale', 'balanced');
        }
    }
};
