<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('theme_foundation.enable_lazy_loading')) {
            $this->migrator->add('theme_foundation.enable_lazy_loading', true);
        }

        if (! $this->migrator->exists('theme_foundation.minify_assets')) {
            $this->migrator->add('theme_foundation.minify_assets', true);
        }
    }
};
