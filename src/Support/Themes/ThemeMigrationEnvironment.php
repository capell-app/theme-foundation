<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Themes;

use Illuminate\Support\Facades\DB;

/**
 * Identifies the installation a theme migration ran on, so a rollback report
 * copied to another environment (for example staging cloned from production,
 * with matching theme ids) is refused rather than applied there.
 */
final class ThemeMigrationEnvironment
{
    public static function fingerprint(): string
    {
        $key = config('app.key');
        $connection = DB::connection();

        return hash('sha256', implode('|', [
            is_string($key) ? $key : '',
            $connection->getName() ?? '',
            (string) $connection->getDatabaseName(),
        ]));
    }
}
