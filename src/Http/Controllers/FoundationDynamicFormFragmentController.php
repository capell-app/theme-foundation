<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Http\Controllers;

use Capell\LayoutBuilder\Actions\Fragments\RenderPublicFragmentAction;
use Illuminate\Http\Response;

/**
 * Serves the real, session-bound form markup for a Foundation section's
 * deferred-fragment placeholder (CAP-0233).
 *
 * Deliberately NOT layout-builder's shared `/_fragments/{reference}` route:
 * that route is architecturally a stateless, visitor-independent artifact —
 * `LayoutBuilderServiceProvider` unconditionally strips `Set-Cookie` and
 * forces `Cache-Control: public, max-age=300, stale-while-revalidate=60` on
 * every response under `_fragments/*`, and the route itself carries no
 * session middleware. A CSRF token baked into that response would be shared
 * across every visitor who resolves the same stable reference within that
 * cache window — the exact bug CAP-0233 exists to fix, just moved one layer
 * down. This route reuses the same reference format and the same generic
 * `RenderPublicFragmentAction` (decode, context/version checks,
 * `BuildPublicLayoutGraphAction`, authoring-surface safety), so the two
 * routes stay behaviourally identical for everything except caching and
 * session availability, and answers with headers safe for session-bound
 * content instead.
 */
final class FoundationDynamicFormFragmentController
{
    public function __invoke(string $reference): Response
    {
        $result = RenderPublicFragmentAction::make()->result($reference);
        $html = $result->html;

        if (! $result->outcome->isRendered() || ! is_string($html) || $html === '') {
            $status = $result->outcome->isRendered() ? Response::HTTP_INTERNAL_SERVER_ERROR : $result->outcome->httpStatus();

            return response('', $status)
                ->header('Cache-Control', 'private, no-store')
                ->header('X-Robots-Tag', 'noindex');
        }

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex');
    }
}
