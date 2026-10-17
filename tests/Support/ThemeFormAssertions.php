<?php

declare(strict_types=1);

use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Capell\FoundationTheme\Tests\Support\NoOptionalExtensions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Livewire\Blaze\Blaze;

require_once __DIR__ . '/DomQueries.php';
require_once __DIR__ . '/NoOptionalExtensions.php';

function assertThemeFormOutcomes(string $theme, string $source, string $formId): void
{
    View::addNamespace('capell-theme-' . $theme, dirname(__DIR__, 3) . '/theme-' . $theme . '/resources/views');
    app('translator')->addNamespace('capell-theme-' . $theme, dirname(__DIR__, 3) . '/theme-' . $theme . '/resources/lang');
    Route::post('/subscribe', static fn (): string => 'Subscribed')->name('capell-newsletter.subscribe');
    app('router')->getRoutes()->refreshNameLookups();
    app()->instance(OptionalExtensionAvailability::class, new NoOptionalExtensions);
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    $viewsPath = dirname(__DIR__, 3) . '/theme-' . $theme . '/resources/views';
    try {
        $newsletter = View::file($viewsPath . '/sections/newsletter.blade.php', ['section' => []])->render();
        $form = View::file($viewsPath . '/sections/form.blade.php', ['section' => [
            'fallback_url' => 'mailto:editor@example.test',
            'fallback_label' => 'Email the editor',
            'fallback_message' => 'Submission unavailable',
        ]])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect(domQueryCount($newsletter, '//form[@method="post"][@action="/subscribe"]//input[@name="source"][@value="' . $source . '"]'))->toBe(1)
        ->and(domQueryCount($newsletter, '//input[@type="email"][@required][@aria-describedby = //*[@id][normalize-space(.) != ""]/@id]'))->toBe(1)
        ->and(domQueryCount($newsletter, '//label[@for = //input[@type="email"]/@id]'))->toBe(1)
        ->and(domQueryCount($form, '//section[@id="' . $formId . '"]//a[@href="mailto:editor@example.test"]'))->toBe(1)
        ->and($form)->toContain('Email the editor', 'Submission unavailable')
        ->not->toContain('wire:snapshot', 'field_path', 'signed');
}
