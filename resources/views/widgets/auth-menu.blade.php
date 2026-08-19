@if ($renderData->isLazyResponse)
    @if ($renderData->isAuthenticated)
        <details class="relative">
            <summary
                aria-label="{{ __('capell-theme-foundation::generic.account_menu') }}"
                class="capell-product-nav-item nav-item cursor-pointer list-none"
            >
                <span
                    aria-hidden="true"
                    class="inline-flex size-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white"
                    >{{ $renderData->avatarInitials }}</span
                >
                <span class="sr-only">{{ $renderData->displayName }}</span>
            </summary>
            <div
                class="absolute right-0 z-20 mt-2 min-w-40 rounded-md bg-white p-2 shadow-lg dark:bg-slate-900"
            >
                @if ($renderData->accountUrl !== null)
                    <a
                        href="{{ $renderData->accountUrl }}"
                        class="block rounded px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >{{ __('capell-theme-foundation::generic.account') }}</a
                    >
                @endif
            </div>
        </details>
    @else
        <a
            href="{{ $renderData->loginUrl }}"
            class="capell-product-nav-item nav-item"
            >{{ __('capell-theme-foundation::generic.log_in') }}</a
        >
    @endif
@else
    <div
        @if (($authMenuFragmentUrl ?? null) !== null)
            data-deferred-fragment
            data-deferred-fragment-url="{{ $authMenuFragmentUrl }}"
            data-deferred-fragment-refresh="pageshow"
            data-auth-menu-placeholder
            data-auth-menu-login-url="{{ $renderData->loginUrl }}"
            data-auth-menu-login-label="{{ __('capell-theme-foundation::generic.log_in') }}"
        @endif
    >
        <a
            href="{{ $renderData->loginUrl }}"
            class="capell-product-nav-item nav-item"
            >{{ __('capell-theme-foundation::generic.log_in') }}</a
        >
    </div>
@endif
