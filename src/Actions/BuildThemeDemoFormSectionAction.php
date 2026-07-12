<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildThemeDemoFormSectionAction
{
    use AsAction;

    /**
     * @return array<string, mixed>
     */
    public function handle(
        string $themeKey,
        string $heading,
        string $summary,
        string $fallbackUrl,
        string $fallbackLabel,
        string $successMessage = 'Thanks — your enquiry has been received.',
    ): array {
        return [
            'type' => 'form',
            'heading' => $heading,
            'summary' => $summary,
            'form_handle' => Str::slug($themeKey . '-enquiry'),
            'form_instance_id' => Str::slug($themeKey . '-contact-form'),
            'form_name' => $heading,
            'form_description' => $summary,
            'success_message' => $successMessage,
            'fallback_message' => 'If the online form is unavailable, use the direct contact route instead.',
            'fallback_label' => $fallbackLabel,
            'fallback_url' => $fallbackUrl,
            'fields' => [
                [
                    'name' => 'name',
                    'label' => 'Name',
                    'type' => 'text',
                    'required' => true,
                    'autocomplete' => 'name',
                ],
                [
                    'name' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'required' => true,
                    'autocomplete' => 'email',
                    'validation_rules' => ['email'],
                ],
                [
                    'name' => 'enquiry_type',
                    'label' => 'What can we help with?',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'project' => 'Project enquiry',
                        'submission' => 'Submission',
                        'partnership' => 'Partnership',
                        'other' => 'Something else',
                    ],
                ],
                [
                    'name' => 'message',
                    'label' => 'Message',
                    'type' => 'textarea',
                    'required' => true,
                ],
            ],
        ];
    }
}
