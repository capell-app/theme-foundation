<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Fragments;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\FoundationTheme\Enums\FoundationSectionWidgetComponentEnum;
use Capell\FoundationTheme\View\Components\Widget\FoundationSection;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Fluent;

/**
 * Serves the real, session-bound `<form>` markup for the Foundation section
 * types that `FoundationSection` delivers dynamically — `form`
 * and `contact-split` when no `form_handle` is configured. Those sections
 * render a deferred-fragment placeholder into the ordinary (cacheable) page
 * response instead of the raw form, so the form itself is only ever
 * rendered here, once per visitor, when the browser fetches the
 * placeholder's fragment URL.
 *
 * This contributor is tagged onto layout-builder's shared
 * `PublicLayoutWidgetPayloadContributor::TAG`, which also backs its
 * `/_fragments/{reference}` route — a route that unconditionally strips
 * `Set-Cookie` and forces `Cache-Control: public, max-age=300` on every
 * response (see FoundationThemeServiceProvider::registerDynamicFormFragmentRoute()
 * for why that is unsafe for a CSRF-bearing form). Nothing about the
 * reference token distinguishes which route resolved it — the same
 * encrypted reference is valid on both — so this contributor only answers
 * when the current route is theme-foundation's own, session-aware
 * `capell-theme-foundation.dynamic-form-fragments.show`; on any other route
 * (including the shared one) it declines, exactly as if this contributor
 * did not exist, so a reference lifted from the placeholder's URL cannot be
 * replayed against the shared, publicly-cached route to leak a token.
 *
 * @see FoundationSection
 */
final class FoundationSectionPublicLayoutWidgetPayloadContributor implements PublicLayoutWidgetPayloadContributor
{
    private const string SAFE_ROUTE_NAME = 'capell-theme-foundation.dynamic-form-fragments.show';

    /**
     * Foundation section component key => the shared partial that renders
     * its real form markup. Only the two section types FoundationSection
     * delivers dynamically are listed; every other Foundation section
     * renders synchronously as part of the ordinary page response and is
     * never requested through this route.
     *
     * @var array<string, string>
     */
    private const array DYNAMIC_DELIVERY_VIEWS_BY_COMPONENT = [
        FoundationSectionWidgetComponentEnum::Form->value => 'capell-theme-foundation::theme.sections.partials.form-body',
        FoundationSectionWidgetComponentEnum::ContactSplit->value => 'capell-theme-foundation::theme.sections.partials.contact-split-form-body',
    ];

    public function priority(): int
    {
        return 20;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
    {
        return [];
    }

    public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string
    {
        if (! $this->isSafeRoute()) {
            return null;
        }

        $view = self::DYNAMIC_DELIVERY_VIEWS_BY_COMPONENT[$widget->getMetaComponent()] ?? null;

        if ($view === null) {
            return null;
        }

        $sectionMeta = $this->sourceSectionMeta($widget);

        if ($sectionMeta === null) {
            return null;
        }

        return view($view, [
            'section' => new Fluent($sectionMeta),
            'formFields' => is_array($sectionMeta['fields'] ?? null) ? $sectionMeta['fields'] : [],
        ])->render();
    }

    /**
     * BuildPublicLayoutGraphAction strips the widget's `meta` attribute down
     * to a narrow public-API-safe allowlist (widget_key, widget_settings,
     * ...) before any contributor runs, so `$widget->meta` never carries the
     * section's configured heading/fields/action/etc. by the time it
     * reaches this contributor. The real, un-stripped meta is re-read by
     * the widget's primary key instead.
     *
     * @return array<string, mixed>|null
     */
    private function sourceSectionMeta(Widget $widget): ?array
    {
        $widgetId = $widget->getKey();

        if ($widgetId === null) {
            return null;
        }

        $sourceWidget = Widget::query()->whereKey($widgetId)->first();

        if (! $sourceWidget instanceof Widget) {
            return null;
        }

        return is_array($sourceWidget->meta) ? $sourceWidget->meta : [];
    }

    private function isSafeRoute(): bool
    {
        return request()->route()?->getName() === self::SAFE_ROUTE_NAME;
    }
}
