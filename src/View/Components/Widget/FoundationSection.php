<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget;

use Capell\FoundationTheme\Actions\ResolveFoundationSectionAnchorAction;
use Capell\LayoutBuilder\Actions\Fragments\BuildLayoutBuilderFragmentReferenceAction;
use Illuminate\Support\Fluent;

final class FoundationSection extends AbstractWidget
{
    /** @var array<string, string> */
    private const array SECTION_VIEWS = [
        'navigation' => 'capell-theme-foundation::theme.sections.navigation',
        'hero' => 'capell-theme-foundation::theme.sections.hero',
        'features' => 'capell-theme-foundation::theme.sections.features',
        'proof' => 'capell-theme-foundation::theme.sections.proof',
        'content-listing' => 'capell-theme-foundation::theme.sections.content-listing',
        'search' => 'capell-theme-foundation::theme.sections.search',
        'pagination' => 'capell-theme-foundation::theme.sections.pagination',
        'form' => 'capell-theme-foundation::theme.sections.form',
        'contact-split' => 'capell-theme-foundation::theme.sections.contact-split',
        'cta' => 'capell-theme-foundation::theme.sections.cta',
        'footer' => 'capell-theme-foundation::theme.sections.footer',
    ];

    /**
     * Section types whose "no form_handle configured" branch renders a raw,
     * session-bound `@csrf` `<form>`. Rendered synchronously as part of the
     * ordinary page response, that form has no delivery-mode veto stopping
     * it reaching the shared full-page HTML cache (CAP-0216/CAP-0233), so
     * these section types are delivered dynamically instead: viewData()
     * exposes a deferred-fragment placeholder URL, and
     * FoundationSectionPublicLayoutWidgetPayloadContributor renders the real
     * form — with a fresh, per-visitor token — when that fragment URL is
     * fetched.
     *
     * @var list<string>
     */
    private const array DYNAMIC_FORM_DELIVERY_SECTION_TYPES = ['form', 'contact-split'];

    /** @var list<string> */
    private const array ANCHORABLE_SECTION_TYPES = [
        'features',
        'proof',
        'content-listing',
        'search',
        'pagination',
        'form',
        'contact-split',
        'cta',
    ];

    protected static string $defaultView = 'capell-theme-foundation::components.widget.foundation-section';

    /**
     * @return array{
     *     anchorable: bool,
     *     sectionAnchor: string,
     *     section: Fluent<array-key, mixed>,
     *     sectionType: string,
     *     sectionView: string|null,
     *     formDeliveryFragmentUrl: string|null
     * }
     */
    protected function viewData(): array
    {
        $sectionMeta = is_array($this->widget->meta) ? $this->widget->meta : [];
        $sectionType = is_string($sectionMeta['type'] ?? null) ? $sectionMeta['type'] : '';

        if ($sectionType === 'features' && is_array($sectionMeta['items'] ?? null)) {
            $sectionMeta['features'] = $sectionMeta['items'];
        }

        $sectionAnchor = ResolveFoundationSectionAnchorAction::run(
            sectionType: $sectionType,
            configuredAnchor: is_string($sectionMeta['anchor'] ?? null) ? $sectionMeta['anchor'] : null,
            containerKey: $this->containerKey,
            widgetKey: (string) $this->widget->key,
            widgetIndex: $this->widgetIndex,
            occurrence: $this->occurrence,
        );

        return [
            'anchorable' => in_array($sectionType, self::ANCHORABLE_SECTION_TYPES, true),
            'sectionAnchor' => $sectionAnchor,
            'section' => new Fluent($sectionMeta),
            'sectionType' => $sectionType,
            'sectionView' => self::SECTION_VIEWS[$sectionType] ?? null,
            'formDeliveryFragmentUrl' => $this->resolveFormDeliveryFragmentUrl($sectionType),
        ];
    }

    /**
     * Computes the deferred-fragment placeholder URL for a section type that
     * needs dynamic form delivery. Returns null for every other section type
     * and — as a fail-closed default — when the current render context
     * cannot produce a fragment reference (e.g. no resolved page/site;
     * BuildLayoutBuilderFragmentReferenceAction requires the full frontend
     * context that is always present during a real page render). The Blade
     * views only consume this value when their own `form_handle` branch is
     * not taken, so it is safe to compute unconditionally here rather than
     * duplicate that per-view field-naming logic in PHP.
     *
     * Reuses the same reference format layout-builder's own
     * PresentationDeliveryMode::LazyFragment mechanism uses, but the URL
     * points at theme-foundation's own dynamic-form-delivery route rather
     * than the shared `/_fragments/{reference}` route — see
     * FoundationThemeServiceProvider::registerDynamicFormFragmentRoute() for
     * why a session-bound CSRF token cannot safely be served from the shared
     * route.
     */
    private function resolveFormDeliveryFragmentUrl(string $sectionType): ?string
    {
        if (! in_array($sectionType, self::DYNAMIC_FORM_DELIVERY_SECTION_TYPES, true)) {
            return null;
        }

        $reference = BuildLayoutBuilderFragmentReferenceAction::run($this->containerKey, $this->occurrence, $this->widget);

        if (! is_string($reference) || $reference === '') {
            return null;
        }

        return route('capell-theme-foundation.dynamic-form-fragments.show', ['reference' => $reference], absolute: false);
    }
}
