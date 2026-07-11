<?php

declare(strict_types=1);

use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;
use Illuminate\Support\Facades\Blade;

it('renders a public-safe fallback when the optional form component is unavailable', function (): void {
    $availability = Mockery::mock(OptionalExtensionAvailability::class);
    $availability->shouldReceive('livewireComponentAvailable')
        ->once()
        ->andReturnFalse();
    $this->app->instance(OptionalExtensionAvailability::class, $availability);

    $html = Blade::render(<<<'BLADE'
        <x-capell::form-embed
            handle="contact-enquiry"
            fallback-message="Send the brief by email instead."
            fallback-label="Email the team"
            fallback-url="mailto:hello@example.test"
            class="theme-contact-form"
        />
        BLADE);

    expect($html)->toContain('class="theme-contact-form"')
        ->toContain('Send the brief by email instead.')
        ->toContain('mailto:hello@example.test')
        ->toContain('Email the team')
        ->not->toContain('capell-form-builder')
        ->not->toContain('Capell\\FormBuilder');
});
