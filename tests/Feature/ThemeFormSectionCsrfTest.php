<?php

declare(strict_types=1);

/*
 * CAP-0233: the raw, CSRF-protected `<form>` markup this test originally
 * exercised directly against theme.sections.{form,contact-split,form--encouraging}
 * now lives in the dedicated `theme.sections.partials.*` views instead — the
 * section views themselves render a deferred-fragment placeholder for the
 * "no form_handle configured" branch so a session-bound `_token` never bakes
 * into the shared, cacheable page response (see
 * FoundationSectionDynamicFormDeliveryTest.php for that half of the fix).
 * The partial is only ever rendered server-side by
 * FoundationSectionPublicLayoutWidgetPayloadContributor, once per visitor,
 * when the browser fetches the placeholder's fragment URL — but the form
 * markup itself, and the CSRF protection it must genuinely provide for a
 * real submission, is unchanged. This file keeps proving that.
 */

/**
 * @param  array<string, mixed>  $sectionData
 */
function renderFoundationFormSection(string $view, array $sectionData): string
{
    $data = ['section' => (object) $sectionData];

    return match ($view) {
        'form' => view('capell-theme-foundation::theme.sections.partials.form-body', [
            ...$data,
            'formFields' => $sectionData['fields'] ?? [],
        ])->render(),
        'form--encouraging' => view('capell-theme-foundation::theme.sections.partials.form-encouraging-body', [
            ...$data,
            'formFields' => $sectionData['fields'] ?? [],
        ])->render(),
        'contact-split' => view('capell-theme-foundation::theme.sections.partials.contact-split-form-body', $data)->render(),
        default => throw new InvalidArgumentException("Unknown Foundation form section [{$view}]."),
    };
}

function foundationFormDocument(string $html): DOMXPath
{
    if ($html === '') {
        throw new RuntimeException('Expected rendered form section HTML.');
    }

    $document = new DOMDocument;

    $previousUseInternalErrors = libxml_use_internal_errors(true);

    try {
        $document->loadHTML($html);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);
    }

    return new DOMXPath($document);
}

it('renders a CSRF-protected customer-wired POST form', /** @param array<string, mixed> $sectionData */ function (string $view, array $sectionData): void {
    session()->start();
    session()->regenerateToken();

    $html = renderFoundationFormSection($view, [
        'action' => '/customer/contact',
        ...$sectionData,
    ]);
    $document = foundationFormDocument($html);
    $forms = $document->query('//form[@method="post" and @action="/customer/contact"]');
    $csrfFields = $document->query('//form[@method="post"]//input[@type="hidden" and @name="_token"]');

    if ($forms === false || $csrfFields === false) {
        throw new RuntimeException('Unable to query the rendered form section DOM.');
    }

    expect($forms)->toHaveCount(1)
        ->and($csrfFields)->toHaveCount(1);

    $csrfField = $csrfFields->item(0);

    if (! $csrfField instanceof DOMElement) {
        throw new RuntimeException('Expected the rendered CSRF field to be an input element.');
    }

    expect($csrfField->getAttribute('value'))->toBe(csrf_token());

    $unsafeMarkers = [
        'authoring',
        'data-theme-key',
        'filament',
        'wire:',
        'data-field',
        'data-model',
        'field_path',
        'model_id',
        'permission',
        'signed-editor',
        'signed_url',
        'widget_state',
        'capell-app/theme-foundation',
    ];

    foreach ($unsafeMarkers as $unsafeMarker) {
        expect(strtolower($html))->not->toContain($unsafeMarker);
    }
})->with([
    'form section' => [
        'form',
        [
            'fields' => [
                ['label' => 'Email', 'name' => 'email', 'type' => 'email'],
            ],
        ],
    ],
    'encouraging form section' => [
        'form--encouraging',
        [
            'fields' => [
                ['label' => 'Email', 'name' => 'email', 'type' => 'email'],
            ],
        ],
    ],
    'contact split section' => [
        'contact-split',
        [],
    ],
]);

it('groups pricing spectrum radio tiers in named fieldsets', function (): void {
    $tiers = [
        ['label' => 'Starter', 'price' => '£19'],
        ['label' => 'Studio', 'price' => '£49'],
    ];

    foreach ([
        'capell-theme-foundation::theme.sections.pricing-value-spectrum',
        'capell-theme-foundation::theme.sections.pricing-value-spectrum--compact',
    ] as $view) {
        $html = view($view, [
            'section' => (object) [
                'heading' => 'Choose your tier',
                'tiers' => $tiers,
            ],
        ])->render();
        $document = foundationFormDocument($html);
        $legends = $document->query('//fieldset[legend[normalize-space()] and .//input[@type="radio"]]');
        $radioInputs = $document->query('//fieldset//input[@type="radio"]');
        $ungroupedRadioInputs = $document->query('//input[@type="radio" and not(ancestor::fieldset[legend[normalize-space()]])]');

        if ($legends === false || $radioInputs === false || $ungroupedRadioInputs === false) {
            throw new RuntimeException('Unable to query the rendered pricing spectrum DOM.');
        }

        expect($legends)->toHaveCount(1)
            ->and($radioInputs)->toHaveCount(2)
            ->and($ungroupedRadioInputs)->toHaveCount(0);
    }
});
