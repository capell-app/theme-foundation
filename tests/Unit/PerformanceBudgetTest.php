<?php

declare(strict_types=1);

use Capell\FoundationTheme\Filament\Settings\FoundationThemeSettingsSchema;
use Filament\Schemas\Schema;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

it('renders the shared theme wrapper inside the declared frontend budget without database queries', function (): void {
    $manifest = foundationThemePerformanceManifest();
    $queryCount = 0;

    DB::listen(static function (QueryExecuted $query) use (&$queryCount): void {
        $queryCount++;
    });

    $startedAt = hrtime(true);

    $html = view('capell-theme-foundation::theme.page', [
        'brand' => new readonly class
        {
            /**
             * @return array<string, string>
             */
            public function tokens(): array
            {
                return [
                    '--theme-primary' => '#315f8f',
                    '--theme-accent' => '#7c5f3f',
                    '--theme-surface' => '#faf9f7',
                    '--theme-foreground' => '#111827',
                    '--theme-body-font' => 'Inter',
                ];
            }
        },
        'content' => '<section><h1>Foundation budget render</h1></section>',
    ])->render();

    $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

    expect($elapsedMilliseconds)->toBeLessThanOrEqual(foundationThemePerformanceFloat($manifest, 'performance.frontendRenderBudgetMs', 20.0))
        ->and($queryCount)->toBe(0)
        ->and($html)->toContain('id="main-content"')
        ->and($html)->toContain('id="theme-status"')
        ->and($html)->toContain('Foundation budget render');
});

it('builds the Foundation settings admin schema inside the declared query budget', function (): void {
    $manifest = foundationThemePerformanceManifest();
    $queryCount = 0;

    DB::listen(static function (QueryExecuted $query) use (&$queryCount): void {
        if (str_starts_with(strtolower($query->sql), 'select')) {
            $queryCount++;
        }
    });

    $components = FoundationThemeSettingsSchema::make(Schema::make());

    expect($queryCount)->toBeLessThanOrEqual(foundationThemePerformanceInt($manifest, 'performance.adminQueryBudget', 40))
        ->and($components)->toHaveCount(3);
});

/**
 * @return array<string, mixed>
 */
function foundationThemePerformanceManifest(): array
{
    return foundationThemePerformanceJsonMap(json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/capell.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    ));
}

/**
 * @return array<string, mixed>
 */
function foundationThemePerformanceJsonMap(mixed $value): array
{
    throw_unless(is_array($value), RuntimeException::class, 'Expected Foundation Theme performance manifest array.');

    $map = [];

    foreach ($value as $key => $item) {
        if (is_string($key)) {
            $map[$key] = $item;
        }
    }

    return $map;
}

/**
 * @param  array<string, mixed>  $manifest
 */
function foundationThemePerformanceFloat(array $manifest, string $key, float $default): float
{
    $value = data_get($manifest, $key, $default);

    return is_int($value) || is_float($value) ? (float) $value : $default;
}

/**
 * @param  array<string, mixed>  $manifest
 */
function foundationThemePerformanceInt(array $manifest, string $key, int $default): int
{
    $value = data_get($manifest, $key, $default);

    if (is_int($value)) {
        return $value;
    }

    return is_string($value) && ctype_digit($value) ? (int) $value : $default;
}
