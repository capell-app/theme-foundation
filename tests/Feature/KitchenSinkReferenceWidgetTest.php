<?php

declare(strict_types=1);

use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Capell\FoundationTheme\Tests\Support\NoOptionalExtensions;
use Illuminate\Support\Facades\View;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Blaze\Blaze;

require_once __DIR__ . '/../Support/WidgetViewFixture.php';
require_once __DIR__ . '/../Support/DomQueries.php';
require_once __DIR__ . '/../Support/NoOptionalExtensions.php';

it('renders kitchen sink reference widgets with semantic accessibility hooks', function (): void {
    app()->instance(OptionalExtensionAvailability::class, new NoOptionalExtensions);
    $widget = foundationWidgetViewFixture(['sections' => array_map(
        static fn (string $key): array => ['key' => $key, 'heading' => 'Reference ' . $key],
        ['tabs', 'faq-accordion', 'carousel-slider', 'full-form'],
    )], count: 0);
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();
    try {
        $html = View::make('capell-theme-foundation::components.widget.kitchen-sink.reference', [
            'widget' => $widget, 'container' => [], 'containerKey' => 'main', 'containerWidth' => null,
            'loop' => (object) ['index' => 0], 'attributes' => new ComponentAttributeBag,
        ])->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect(domQueryCount($html, '//*[@role="tablist"][@aria-label="' . __('capell-theme-foundation::generic.demo_tab_set') . '"]'))->toBe(1)
        ->and(domQueryCount($html, '//*[@role="tab"][@aria-controls = //*[@role="tabpanel"]/@id]'))->toBe(2)
        ->and(domQueryCount($html, '//*[@role="tabpanel"][@aria-labelledby = //*[@role="tab"]/@id]'))->toBe(2)
        ->and(domQueryCount($html, '//button[@aria-expanded="false"][@aria-controls = //*[@hidden]/@id]'))->toBe(1)
        ->and(domQueryCount($html, '//*[@aria-roledescription="carousel"]//button[@aria-label]'))->toBe(2)
        ->and(domQueryCount($html, '//form[@action="#"]'))->toBe(0)
        ->and(domQueryCount($html, '//*[@role="note"][@aria-labelledby = //*[@id]/@id]'))->toBe(1)
        ->and($html)->toContain(e(__('capell-theme-foundation::generic.form_unavailable')))
        ->and($html)->toContain('Reference full-form')
        ->not->toContain('signed', 'field_path', 'wire:snapshot');
});
