<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\ResolveThemeFormEmbedDataAction;
use Capell\FoundationTheme\Contracts\OptionalExtensionAvailability;

it('resolves a registered public form component without exposing its package alias', function (): void {
    $availability = Mockery::mock(OptionalExtensionAvailability::class);
    $availability->shouldReceive('livewireComponentAvailable')
        ->once()
        ->with('capell-app/form-builder', 'public-form')
        ->andReturnTrue();

    $data = (new ResolveThemeFormEmbedDataAction($availability))->handle(
        handle: 'contact-enquiry',
        instanceId: 'Theme Contact Form',
        fallbackMessage: 'Email us if the form is unavailable.',
        fallbackLabel: 'Email the team',
        fallbackUrl: 'mailto:hello@example.test',
    );

    expect($data->available)->toBeTrue()
        ->and($data->handle)->toBe('contact-enquiry')
        ->and($data->componentName)->toBe('public-form')
        ->and($data->instanceId)->toBe('theme-contact-form')
        ->and($data->widgetData)->toBe([
            'instance_id' => 'theme-contact-form',
            'fallback_message' => 'Email us if the form is unavailable.',
            'fallback_label' => 'Email the team',
            'fallback_url' => 'mailto:hello@example.test',
        ]);
});

it('does not resolve an optional component for a missing form handle', function (): void {
    $availability = Mockery::mock(OptionalExtensionAvailability::class);
    $availability->shouldNotReceive('livewireComponentAvailable');

    $data = (new ResolveThemeFormEmbedDataAction($availability))->handle(
        handle: null,
        fallbackUrl: 'javascript:alert(1)',
    );

    expect($data->available)->toBeFalse()
        ->and($data->handle)->toBeNull()
        ->and($data->widgetData)->toBe(['instance_id' => 'theme-form']);
});
