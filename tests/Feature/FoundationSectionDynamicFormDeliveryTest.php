<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Dynamic form delivery for cache-unsafe Foundation sections (CAP-0233)
|--------------------------------------------------------------------------
|
| CAP-0216 added a literal @csrf to the "no form_handle configured" branch
| of the `form` and `contact-split` sections. Those sections render
| synchronously through FoundationSection, a plain-Blade layout-widget with
| no delivery-mode veto, so the resulting `<input type="hidden"
| name="_token" ...>` is whoever's session happened to populate the shared
| full-page HTML cache — every later visitor gets a foreign, invalid token
| and their submission fails.
|
| This proves the fix end to end:
|   1. FoundationSection::viewData(), exercised through the real component
|      constructor with a real, persisted Widget/containerKey/occurrence and
|      a real bound frontend context, computes a deferred-fragment
|      placeholder URL for `form`/`contact-split` only.
|   2. The section Blade views render that placeholder (and never a literal
|      `_token`) when no form_handle is configured — the same direct-render
|      technique ThemeFormSectionCsrfTest.php already established.
|   3. Fetching that real placeholder URL through the real, theme-foundation-
|      owned `/_capell/foundation-dynamic-form/{reference}` route — served
|      by FoundationSectionPublicLayoutWidgetPayloadContributor via
|      FoundationDynamicFormFragmentController — returns the real form, with
|      a fresh, valid, working CSRF token.
|   4. That same reference cannot be replayed against layout-builder's
|      shared, publicly-cached `/_fragments/{reference}` route to leak a
|      token: FoundationSectionPublicLayoutWidgetPayloadContributor only
|      answers on theme-foundation's own route.
|
| (1) and (2) are exercised separately rather than through FoundationSection's
| own outer `foundation-section.blade.php` wrapper: that view lives under
| theme-foundation's `resources/views/components/`, which
| tests/Packages/PackagesTestCase forces through Livewire Blaze compilation
| for every test in this suite (see registerBlazeOptimizedViews()) — the same
| known component-stack-desync class already documented and worked around in
| LayoutNativeMainContentRendersWidgetsTest.php. Rendering that wrapper in
| isolation (outside a full page's exact adjacent-component-tag sequence)
| reproducibly renders empty with no exception; that is an existing Blade/
| Blaze test-harness quirk unrelated to CAP-0233, not evidence this fix is
| broken. Exercising FoundationSection's real PHP decision logic
| (constructor + viewData()) plus the real, unmodified section Blade views
| covers the same code CAP-0216 touched without depending on that wrapper.
*/

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Enums\FoundationSectionWidgetComponentEnum;
use Capell\FoundationTheme\View\Components\Widget\FoundationSection;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\State\FrontendState;
use Capell\LayoutBuilder\Actions\Fragments\BuildLayoutBuilderFragmentReferenceAction;
use Capell\LayoutBuilder\Models\Widget;

/**
 * @param  array<string, mixed>  $sectionMeta
 * @return array{site: Site, language: Language, layout: Layout, page: Page, widget: Widget}
 */
function foundationDynamicFormDeliveryFixture(string $sectionType, string $component, array $sectionMeta): array
{
    $language = Language::factory()->create(['status' => true]);
    $site = Site::factory()->language($language)->withTranslations($language)->create(['status' => true]);
    $widget = Widget::factory()->create([
        'key' => 'foundation-' . $sectionType . '-cap-0233-' . uniqid(),
        'status' => true,
        'component' => $component,
        'meta' => ['type' => $sectionType, ...$sectionMeta],
    ]);
    $layout = Layout::factory()->site($site)->create([
        'status' => true,
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
        ],
    ]);
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->withTranslations($language)
        ->create([
            'visible_from' => now()->subDay(),
            'visible_until' => null,
        ]);

    return [
        'site' => $site,
        'language' => $language,
        'layout' => $layout,
        'page' => $page,
        'widget' => $widget,
    ];
}

/**
 * @param  array{site: Site, language: Language, layout: Layout, page: Page, widget: Widget}  $fixture
 */
function bindFoundationDynamicFormDeliveryFrontendContext(array $fixture): void
{
    Frontend::clearResolvedInstance(FrontendContextReader::class);
    app()->instance(
        FrontendContextReader::class,
        (new FrontendState)
            ->withSite($fixture['site'])
            ->withLanguage($fixture['language'])
            ->withPage($fixture['page'])
            ->withLayout($fixture['layout']),
    );
}

/**
 * Exercises FoundationSection's real constructor + protected viewData()
 * method (the CAP-0233 decision logic) without going through the
 * Blaze-affected outer component wrapper — see the file-level note above.
 *
 * @return array<array-key, mixed>
 */
function foundationSectionViewData(Widget $widget): array
{
    $component = new FoundationSection(
        container: [],
        containerKey: 'main',
        widgetIndex: 0,
        loop: new stdClass,
        widget: $widget,
        widgetData: [],
        pageSlot: null,
        occurrence: 1,
    );

    $viewData = (new ReflectionMethod($component, 'viewData'))->invoke($component);

    if (! is_array($viewData)) {
        throw new LogicException('FoundationSection::viewData() must return an array.');
    }

    return $viewData;
}

/**
 * @param  array<array-key, mixed>  $viewData
 * @return non-empty-string
 */
function foundationFormDeliveryFragmentUrl(array $viewData): string
{
    $fragmentUrl = $viewData['formDeliveryFragmentUrl'] ?? null;

    if (! is_string($fragmentUrl) || $fragmentUrl === '') {
        throw new LogicException('FoundationSection must provide a dynamic form fragment URL.');
    }

    return $fragmentUrl;
}

it('computes a real deferred-fragment placeholder URL for the base form section', function (): void {
    $fixture = foundationDynamicFormDeliveryFixture('form', FoundationSectionWidgetComponentEnum::Form->value, [
        'heading' => 'Talk to us',
        'action' => '/customer/contact',
        'fields' => [['label' => 'Email', 'name' => 'email', 'type' => 'email']],
    ]);
    bindFoundationDynamicFormDeliveryFrontendContext($fixture);

    $viewData = foundationSectionViewData($fixture['widget']);
    $fragmentUrl = foundationFormDeliveryFragmentUrl($viewData);

    expect($viewData['sectionType'])->toBe('form')
        ->and($fragmentUrl)->toContain('/_capell/foundation-dynamic-form/')
        ->and($fragmentUrl)->not->toStartWith('http://')
        ->and($fragmentUrl)->not->toStartWith('https://')
        ->and($fragmentUrl)->not->toContain('/_fragments/');
});

it('computes a real deferred-fragment placeholder URL for the contact-split section', function (): void {
    $fixture = foundationDynamicFormDeliveryFixture('contact-split', FoundationSectionWidgetComponentEnum::ContactSplit->value, [
        'heading' => 'Get in touch',
        'action' => '/customer/contact',
    ]);
    bindFoundationDynamicFormDeliveryFrontendContext($fixture);

    $viewData = foundationSectionViewData($fixture['widget']);
    $fragmentUrl = foundationFormDeliveryFragmentUrl($viewData);

    expect($viewData['sectionType'])->toBe('contact-split')
        ->and($fragmentUrl)->toContain('/_capell/foundation-dynamic-form/');
});

it('does not compute a deferred-fragment URL for section types that do not need dynamic delivery', function (): void {
    $fixture = foundationDynamicFormDeliveryFixture('hero', FoundationSectionWidgetComponentEnum::Hero->value, [
        'heading' => 'Welcome',
    ]);
    bindFoundationDynamicFormDeliveryFrontendContext($fixture);

    $viewData = foundationSectionViewData($fixture['widget']);

    expect($viewData['sectionType'])->toBe('hero')
        ->and($viewData['formDeliveryFragmentUrl'])->toBeNull();
});

it('renders the base form section as a deferred-fragment placeholder with no baked CSRF token', function (): void {
    $html = view('capell-theme-foundation::theme.sections.form', [
        'section' => (object) ['heading' => 'Talk to us', 'fields' => [['label' => 'Email', 'name' => 'email', 'type' => 'email']]],
        'formDeliveryFragmentUrl' => 'https://example.test/_capell/foundation-dynamic-form/opaque-reference',
    ])->render();

    expect($html)->toContain('data-deferred-fragment')
        ->and($html)->toContain('data-deferred-fragment-url="https://example.test/_capell/foundation-dynamic-form/opaque-reference"')
        ->and($html)->not->toContain('_token')
        ->and($html)->not->toContain('<form');
});

it('renders the contact-split section as a deferred-fragment placeholder with no baked CSRF token', function (): void {
    $html = view('capell-theme-foundation::theme.sections.contact-split', [
        'section' => (object) ['heading' => 'Get in touch'],
        'formDeliveryFragmentUrl' => 'https://example.test/_capell/foundation-dynamic-form/opaque-reference',
    ])->render();

    expect($html)->toContain('data-deferred-fragment')
        ->and($html)->toContain('data-deferred-fragment-url="https://example.test/_capell/foundation-dynamic-form/opaque-reference"')
        ->and($html)->not->toContain('_token')
        ->and($html)->not->toContain('<form');
});

it('renders the form--encouraging variant as a deferred-fragment placeholder with no baked CSRF token', function (): void {
    $html = view('capell-theme-foundation::theme.sections.form--encouraging', [
        'section' => (object) ['heading' => 'Talk to us', 'fields' => [['label' => 'Email', 'name' => 'email', 'type' => 'email']]],
        'formDeliveryFragmentUrl' => 'https://example.test/_capell/foundation-dynamic-form/opaque-reference',
    ])->render();

    expect($html)->toContain('data-deferred-fragment')
        ->and($html)->toContain('data-deferred-fragment-url="https://example.test/_capell/foundation-dynamic-form/opaque-reference"')
        ->and($html)->not->toContain('_token')
        ->and($html)->not->toContain('<form');
});

it('renders nothing unsafe when a fragment URL cannot be resolved', function (): void {
    $html = view('capell-theme-foundation::theme.sections.form', [
        'section' => (object) ['heading' => 'Talk to us', 'fields' => [['label' => 'Email', 'name' => 'email', 'type' => 'email']]],
        'formDeliveryFragmentUrl' => null,
    ])->render();

    expect($html)->not->toContain('_token')
        ->and($html)->not->toContain('<form')
        ->and($html)->not->toContain('data-deferred-fragment');
});

it('keeps rendering the real form_handle-driven form-embed instead of a placeholder', function (): void {
    $html = view('capell-theme-foundation::theme.sections.form', [
        'section' => (object) ['heading' => 'Talk to us', 'form_handle' => 'contact-handle'],
        'formDeliveryFragmentUrl' => 'https://example.test/_capell/foundation-dynamic-form/should-not-be-used',
    ])->render();

    expect($html)->not->toContain('data-deferred-fragment')
        ->and($html)->not->toContain('_token');
});

it('serves the real, CSRF-protected form when the base form section fragment URL is fetched', function (): void {
    $fixture = foundationDynamicFormDeliveryFixture('form', FoundationSectionWidgetComponentEnum::Form->value, [
        'heading' => 'Talk to us',
        'action' => '/customer/contact',
        'submitLabel' => 'Send message',
        'fields' => [['label' => 'Email', 'name' => 'email', 'type' => 'email', 'required' => true]],
    ]);
    bindFoundationDynamicFormDeliveryFrontendContext($fixture);

    $fragmentUrl = foundationFormDeliveryFragmentUrl(foundationSectionViewData($fixture['widget']));

    $response = $this->get($fragmentUrl);

    $response->assertOk()
        ->assertHeaderContains('Cache-Control', 'no-store');

    expect((string) $response->baseResponse->headers->get('Cache-Control'))->not->toContain('public');

    $body = (string) $response->getContent();

    expect($body)->toContain('<form')
        ->and($body)->toContain('action="/customer/contact"')
        ->and($body)->toContain('Send message');

    preg_match('/name="_token"\s+value="([^"]+)"/', $body, $tokenMatches);
    $csrfToken = $tokenMatches[1] ?? null;

    expect($csrfToken)->toBeString()->not->toBe('')
        ->and($csrfToken)->toBe(csrf_token());
});

it('serves the real, CSRF-protected form when the contact-split section fragment URL is fetched', function (): void {
    $fixture = foundationDynamicFormDeliveryFixture('contact-split', FoundationSectionWidgetComponentEnum::ContactSplit->value, [
        'heading' => 'Get in touch',
        'action' => '/customer/contact',
    ]);
    bindFoundationDynamicFormDeliveryFrontendContext($fixture);

    $fragmentUrl = foundationFormDeliveryFragmentUrl(foundationSectionViewData($fixture['widget']));

    $response = $this->get($fragmentUrl);

    $response->assertOk()
        ->assertHeaderContains('Cache-Control', 'no-store');

    expect((string) $response->baseResponse->headers->get('Cache-Control'))->not->toContain('public');

    $body = (string) $response->getContent();

    expect($body)->toContain('<form')
        ->and($body)->toContain('action="/customer/contact"');

    preg_match('/name="_token"\s+value="([^"]+)"/', $body, $tokenMatches);
    $csrfToken = $tokenMatches[1] ?? null;

    expect($csrfToken)->toBeString()->not->toBe('')
        ->and($csrfToken)->toBe(csrf_token());
});

it('does not serve a fragment for a Foundation section type not enrolled in dynamic delivery', function (): void {
    $fixture = foundationDynamicFormDeliveryFixture('hero', FoundationSectionWidgetComponentEnum::Hero->value, [
        'heading' => 'Welcome',
    ]);
    bindFoundationDynamicFormDeliveryFrontendContext($fixture);

    $reference = BuildLayoutBuilderFragmentReferenceAction::run('main', 1, $fixture['widget']);

    expect($reference)->toBeString();

    $response = $this->get(route('capell-theme-foundation.dynamic-form-fragments.show', ['reference' => $reference]));

    $response->assertNotFound();
});

it('refuses to leak a form fragment through the shared, publicly-cached /_fragments/ route', function (): void {
    $fixture = foundationDynamicFormDeliveryFixture('form', FoundationSectionWidgetComponentEnum::Form->value, [
        'heading' => 'Talk to us',
        'action' => '/customer/contact',
        'fields' => [['label' => 'Email', 'name' => 'email', 'type' => 'email']],
    ]);
    bindFoundationDynamicFormDeliveryFrontendContext($fixture);

    $reference = BuildLayoutBuilderFragmentReferenceAction::run('main', 1, $fixture['widget']);

    expect($reference)->toBeString();

    // Same reference, same underlying RenderPublicFragmentAction pipeline —
    // but the shared route, not theme-foundation's own. If this ever starts
    // returning the form, a CSRF token would be servable from a response
    // layout-builder unconditionally marks `Cache-Control: public,
    // max-age=300` — reproducing CAP-0216's bug one layer down.
    $response = $this->get(route('capell-layout-builder.fragments.show', ['reference' => $reference]));

    $response->assertNotFound();
});
