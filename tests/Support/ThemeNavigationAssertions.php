<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Livewire\Blaze\Blaze;

require_once __DIR__ . '/DomQueries.php';

function assertThemeNavigationOutcomes(string $theme, bool $requireCurrentPage = true): void
{
    View::addNamespace('capell-theme-' . $theme, dirname(__DIR__, 3) . '/theme-' . $theme . '/resources/views');
    app()->instance('request', Request::create('/current'));
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    /** @var view-string $navigationView */
    $navigationView = implode('', ['capell-theme-', $theme, '::sections.navigation']);

    try {
        $html = view($navigationView, ['section' => [
            'items' => [
                ['label' => 'Current page', 'url' => '/current'],
                ['label' => 'Other page', 'url' => '/other'],
                ['label' => '', 'url' => '/empty-label'],
                ['label' => 'No destination', 'url' => ''],
            ],
        ]])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect(domQueryCount($html, '//a[@href="/current"]'))->toBeGreaterThan(0, $theme)
        ->and(domQueryCount($html, '//a[@href="/other"]'))->toBeGreaterThan(0, $theme);

    if ($requireCurrentPage) {
        expect(domQueryCount($html, '//a[@href="/empty-label"]'))->toBe(0)
            ->and($html)->not->toContain('No destination');

        expect(domQueryCount($html, '//a[@href="/current"][@aria-current="page"]'))
            ->toBe(domQueryCount($html, '//a[@href="/current"]'), $theme)
            ->and(domQueryCount($html, '//a[@href="/other"][@aria-current]'))->toBe(0);
    } else {
        expect(domQueryCount($html, '//details//summary'))->toBeGreaterThan(0, $theme)
            ->and(domQueryCount($html, '//details//a[@href="/current"]'))->toBeGreaterThan(0, $theme);
    }
}
