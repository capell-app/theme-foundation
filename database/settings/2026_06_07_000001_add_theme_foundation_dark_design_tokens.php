<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'theme_foundation.dark_page_background_color' => '#0f172a',
            'theme_foundation.dark_surface_background_color' => '#111827',
            'theme_foundation.dark_muted_background_color' => '#1f2937',
            'theme_foundation.dark_header_background_color' => '#111827',
            'theme_foundation.dark_border_color' => '#334155',
            'theme_foundation.dark_border_strong_color' => '#475569',
            'theme_foundation.dark_card_background_color' => '#111827',
            'theme_foundation.dark_primary_action_color' => '#93c5fd',
            'theme_foundation.dark_band_background_color' => '#0f172a',
            'theme_foundation.dark_band_alternate_background_color' => '#111827',
            'theme_foundation.dark_band_accent_background_color' => '#1e293b',
            'theme_foundation.dark_band_border_color' => '#334155',
            'theme_foundation.dark_image_border_color' => '#334155',
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
