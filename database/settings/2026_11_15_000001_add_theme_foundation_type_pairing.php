<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Null keeps every existing site on its brand profile's fonts until an editor chooses a pairing.
        if (! $this->migrator->exists('theme_foundation.type_pairing')) {
            $this->migrator->add('theme_foundation.type_pairing', null);
        }
    }
};
