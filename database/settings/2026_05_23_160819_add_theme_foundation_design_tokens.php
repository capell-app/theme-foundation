<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            'theme_foundation.page_background_color' => '#faf9f7',
            'theme_foundation.surface_background_color' => '#ffffff',
            'theme_foundation.muted_background_color' => '#f4f3f1',
            'theme_foundation.header_background_color' => '#fbfaf7',
            'theme_foundation.border_color' => '#e1e5eb',
            'theme_foundation.border_strong_color' => '#c7ced8',
            'theme_foundation.card_background_color' => '#ffffff',
            'theme_foundation.primary_action_color' => '#315f8f',
            'theme_foundation.section_spacing' => 'relaxed',
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
