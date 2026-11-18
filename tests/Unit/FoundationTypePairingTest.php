<?php

declare(strict_types=1);

use Capell\Core\ThemeStudio\Typography\ThemeFontCatalogue;
use Capell\FoundationTheme\Actions\ResolveFoundationThemeTokensAction;
use Capell\FoundationTheme\Settings\FoundationThemeSettings;
use Capell\FoundationTheme\Settings\FoundationThemeSettingsMigrationProvider;
use Capell\Frontend\Facades\Frontend;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

function typePairingHead(?FoundationThemeSettings $settings): string
{
    Frontend::setFrontendData('foundation.theme.tokens', ResolveFoundationThemeTokensAction::run(settings: $settings, resolveSettings: false));

    return Blade::render('<x-capell::app.head.tokens />');
}

it('names only catalogue fonts and shipped scales in every pairing', function (string $pairing, array $definition): void {
    expect(ThemeFontCatalogue::has($definition['heading']))->toBeTrue()
        ->and(ThemeFontCatalogue::has($definition['body']))->toBeTrue()
        ->and(FoundationThemeSettings::HEADING_SCALE_OPTIONS)->toHaveKey($definition['scale'])
        ->and($definition['tracking'])->toMatch('/^-0\.\d+em$/');
})->with(array_map(
    static fn (string $pairing, array $definition): array => [$pairing, $definition],
    array_keys(FoundationThemeSettings::TYPE_PAIRING_OPTIONS),
    FoundationThemeSettings::TYPE_PAIRING_OPTIONS,
));

it('emits the chosen pairing as font stacks that outrank the brand profile', function (): void {
    $settings = new FoundationThemeSettings;
    $settings->type_pairing = 'editorial';
    $settings->heading_scale = 'expressive';

    $html = typePairingHead($settings);

    expect($html)
        ->toContain(':root:root {')
        ->toContain('--theme-heading-font: ' . ThemeFontCatalogue::stack('fraunces') . ';')
        ->toContain('--theme-body-font: ' . ThemeFontCatalogue::stack('source-serif') . ';')
        ->toContain('--foundation-heading-tracking: -0.02em;')
        ->toContain('--foundation-statement-size: ' . FoundationThemeSettings::HEADING_SCALE_OPTIONS['expressive']['statement'] . ';');
});

it('leaves the brand profile fonts alone when no pairing is chosen', function (?string $pairing): void {
    $settings = new FoundationThemeSettings;
    $settings->type_pairing = $pairing;

    $html = typePairingHead($settings);

    expect($html)
        ->not->toContain('--theme-heading-font')
        ->not->toContain('--theme-body-font')
        ->toContain('--foundation-heading-tracking: ' . FoundationThemeSettings::DEFAULT_HEADING_TRACKING . ';');
})->with([
    'no pairing' => [null],
    'unknown pairing' => ['retired-pairing'],
]);

it('emits default tokens when settings cannot be resolved', function (): void {
    $html = typePairingHead(null);

    expect($html)
        ->not->toContain('--theme-heading-font')
        ->toContain('--foundation-statement-size: ' . FoundationThemeSettings::HEADING_SCALE_OPTIONS['balanced']['statement'] . ';');
});

it('adds the pairing setting as empty without overwriting a saved choice', function (): void {
    $migrationName = '2026_11_15_000001_add_theme_foundation_type_pairing';
    expect((new FoundationThemeSettingsMigrationProvider)->getSettingMigrations())->toContain($migrationName);

    $query = static fn () => DB::table('settings')->where('group', 'theme_foundation')->where('name', 'type_pairing');
    $query()->delete();
    $migration = require dirname(__DIR__, 2) . '/database/settings/' . $migrationName . '.php';
    throw_unless($migration instanceof SettingsMigration, RuntimeException::class);

    $migration->up();
    expect($query()->value('payload'))->toBe('null');

    app(SettingsMigrator::class)->update('theme_foundation.type_pairing', static fn (): string => 'journal');
    $migration->up();
    expect($query()->value('payload'))->toBe('"journal"')
        ->and($query()->count())->toBe(1);
});
