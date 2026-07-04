<?php

declare(strict_types=1);

/*
 * Wave 11.8: the catalogue-report command must measure the real fleet and
 * render a scoreboard without falling over, and its docs/themes.json
 * validation must agree with the same source the guard tests read — so the
 * report can be trusted as the catalogue's instrument.
 */

it('renders a per-theme craft scoreboard for the fleet', function (): void {
    $this->artisan('capell:theme-catalogue-report')
        ->expectsOutputToContain('Theme catalogue report')
        ->expectsOutputToContain('View-transition')
        ->expectsOutputToContain('case-study-platform')
        ->expectsOutputToContain('Catalogue validation')
        ->assertExitCode(0);
});

it('reports no catalogue drift when every theme sits within budget and is catalogued', function (): void {
    $this->artisan('capell:theme-catalogue-report')
        ->expectsOutputToContain('No drift')
        ->assertExitCode(0);
});
