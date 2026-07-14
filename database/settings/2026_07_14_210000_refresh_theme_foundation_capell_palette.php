<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $updates = [
            'theme_foundation.page_background_color' => ['#faf9f7', '#f5f7f4'],
            'theme_foundation.surface_background_color' => ['#ffffff', '#fcfffb'],
            'theme_foundation.muted_background_color' => ['#f4f3f1', '#edf2ee'],
            'theme_foundation.header_background_color' => ['#fbfaf7', '#fcfffb'],
            'theme_foundation.border_color' => ['#e1e5eb', '#cfd9d3'],
            'theme_foundation.border_strong_color' => ['#c7ced8', '#aebdb5'],
            'theme_foundation.card_background_color' => ['#ffffff', '#fcfffb'],
            'theme_foundation.primary_action_color' => ['#315f8f', '#087765'],
            'theme_foundation.band_background_color' => ['#faf9f7', '#f5f7f4'],
            'theme_foundation.band_alternate_background_color' => ['#f4f3f1', '#edf2ee'],
            'theme_foundation.band_accent_background_color' => ['#f4f3f1', '#e0f2ec'],
            'theme_foundation.band_border_color' => ['#e1e5eb', '#cfd9d3'],
            'theme_foundation.image_border_color' => ['#e1e5eb', '#cfd9d3'],
            'theme_foundation.dark_page_background_color' => ['#0f172a', '#0b1716'],
            'theme_foundation.dark_surface_background_color' => ['#111827', '#101d1a'],
            'theme_foundation.dark_muted_background_color' => ['#1f2937', '#172621'],
            'theme_foundation.dark_header_background_color' => ['#111827', '#0b1716'],
            'theme_foundation.dark_border_color' => ['#334155', '#31423c'],
            'theme_foundation.dark_border_strong_color' => ['#475569', '#52615b'],
            'theme_foundation.dark_card_background_color' => ['#111827', '#101d1a'],
            'theme_foundation.dark_primary_action_color' => ['#93c5fd', '#79d7c2'],
            'theme_foundation.dark_band_background_color' => ['#0f172a', '#0b1716'],
            'theme_foundation.dark_band_alternate_background_color' => ['#111827', '#101d1a'],
            'theme_foundation.dark_band_accent_background_color' => ['#1e293b', '#17352e'],
            'theme_foundation.dark_band_border_color' => ['#334155', '#31423c'],
            'theme_foundation.dark_image_border_color' => ['#334155', '#31423c'],
        ];

        foreach ($updates as $key => [$previousDefault, $newDefault]) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $newDefault);

                continue;
            }

            $this->migrator->update(
                $key,
                static fn (mixed $value): mixed => $value === $previousDefault ? $newDefault : $value,
            );
        }
    }
};
