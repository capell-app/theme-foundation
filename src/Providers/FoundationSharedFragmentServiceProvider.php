<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\RegistersInstalledRuntime;
use Capell\Core\Support\Packages\RouteGroupIsolation;
use Capell\FoundationTheme\Http\Controllers\FoundationDynamicFormFragmentController;
use Capell\Frontend\Support\Routing\ReservedFrontendPathRegistry;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Override;

final class FoundationSharedFragmentServiceProvider extends ServiceProvider
{
    use RegistersInstalledRuntime;

    private const string DYNAMIC_FORM_FRAGMENT_ROUTE = 'capell-theme-foundation.dynamic-form-fragments.show';

    #[Override]
    public function register(): void
    {
        // Shared rendering serves Layout Builder even while Foundation is uninstalled.
        $this->registerInstalledRuntime(LayoutBuilderServiceProvider::$packageName);
    }

    protected function bootInstalledRuntime(): void
    {
        $this->registerDynamicFormFragmentRoute();
        $this->reserveDynamicFormFragmentPath();
    }

    /**
     * A theme-foundation-owned route for the deferred-fragment
     * placeholder, deliberately separate from layout-builder's shared
     * `/_fragments/{reference}` route. That route unconditionally strips
     * `Set-Cookie` and forces `Cache-Control: public, max-age=300,
     * stale-while-revalidate=60` on every response under `_fragments/*` (see
     * LayoutBuilderServiceProvider's RequestHandled listener) and carries no
     * session middleware — architecturally correct for the stateless,
     * visitor-independent content it was built for, but unsafe for a form
     * that must mint a fresh, working, session-bound CSRF token per visitor:
     * baking one into a response cacheable for five minutes would reproduce
     * the same cross-visitor CSRF-token leak one layer down. `web` middleware here (matching
     * registerLazyLayoutWidgetRoute()'s own `_capell/` convention) gives the
     * real session access `@csrf` needs; FoundationDynamicFormFragmentController
     * answers with `private, no-store` instead.
     */
    private function registerDynamicFormFragmentRoute(): void
    {
        if ($this->dynamicFormFragmentRouteExists()) {
            return;
        }

        RouteGroupIsolation::run($this->app->make(Router::class), function (): void {
            Route::middleware('web')
                ->name('capell-theme-foundation.dynamic-form-fragments.')
                ->group(function (): void {
                    Route::get('/_capell/foundation-dynamic-form/{reference}', FoundationDynamicFormFragmentController::class)
                        ->where('reference', '.*')
                        ->name('show');
                });
        });
    }

    /**
     * The route name is its stable identity. Laravel indexes names assigned
     * after a route is added only on the next lookup refresh, so scan rather
     * than refresh the shared name index.
     */
    private function dynamicFormFragmentRouteExists(): bool
    {
        $routes = $this->app->make(Router::class)->getRoutes();

        if ($routes->hasNamedRoute(self::DYNAMIC_FORM_FRAGMENT_ROUTE)) {
            return true;
        }

        foreach ($routes->getRoutes() as $route) {
            if ($route->getName() === self::DYNAMIC_FORM_FRAGMENT_ROUTE) {
                return true;
            }
        }

        return false;
    }

    private function reserveDynamicFormFragmentPath(): void
    {
        if (! CapellCore::isPackageAvailable('capell-app/frontend')) {
            return;
        }

        $this->app->make(ReservedFrontendPathRegistry::class)->reservePrefix('_capell/foundation-dynamic-form');
    }
}
