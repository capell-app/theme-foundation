<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\BuildThemeDemoFormsPayloadAction;
use Capell\FoundationTheme\Support\Demo\ThemeDemoPageDefinition;

it('serializes form sections into a package-neutral demo payload', function (): void {
    $definition = new ThemeDemoPageDefinition(
        surface: 'contact',
        name: 'Studio contact',
        title: 'Start a project',
        slug: 'theme-studio-contact',
        content: '<p>Tell us about the work.</p>',
        renderData: [
            'sections' => [
                ['type' => 'hero', 'heading' => 'Start a project'],
                [
                    'type' => 'form',
                    'form_handle' => 'studio-enquiry',
                    'form_name' => 'Studio enquiry',
                    'summary' => 'Share the brief.',
                    'success_message' => 'Thanks — we will reply shortly.',
                    'fields' => [
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                    ],
                ],
            ],
        ],
    );

    $payload = json_decode(
        app(BuildThemeDemoFormsPayloadAction::class)->handle([$definition]),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($payload)->toBe([[
        'handle' => 'studio-enquiry',
        'name' => 'Studio enquiry',
        'description' => 'Share the brief.',
        'fields' => [
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
        ],
        'success_message' => 'Thanks — we will reply shortly.',
    ]]);
});
