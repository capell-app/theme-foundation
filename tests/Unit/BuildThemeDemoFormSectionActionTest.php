<?php

declare(strict_types=1);

use Capell\FoundationTheme\Actions\BuildThemeDemoFormSectionAction;

it('builds a stable portable form section for a theme demo', function (): void {
    $section = BuildThemeDemoFormSectionAction::run(
        themeKey: 'studio-index',
        heading: 'Send the project',
        summary: 'Share the work and credits.',
        fallbackUrl: 'mailto:desk@example.test',
        fallbackLabel: 'Email the desk',
    );

    expect($section['type'])->toBe('form')
        ->and($section['form_handle'])->toBe('studio-index-enquiry')
        ->and($section['form_instance_id'])->toBe('studio-index-contact-form')
        ->and($section['fields'])->toHaveCount(4)
        ->and($section['fields'][1]['type'])->toBe('email')
        ->and($section['fallback_url'])->toBe('mailto:desk@example.test');
});
