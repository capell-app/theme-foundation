const selector =
    '[data-auth-menu-placeholder][data-deferred-fragment-refresh="pageshow"]'

const refreshes = new WeakMap()

const loginUrl = (element) => {
    try {
        const url = new URL(
            element.dataset.authMenuLoginUrl ?? '',
            window.location.href,
        )

        if (
            ['http:', 'https:'].includes(url.protocol) &&
            url.origin === window.location.origin
        ) {
            return url.href
        }
    } catch {
        // Fall through to the safe local fallback.
    }

    return '/login'
}

const renderGuestFallback = (element) => {
    const link = document.createElement('a')

    link.href = loginUrl(element)
    link.className = 'capell-product-nav-item nav-item'
    link.textContent = element.dataset.authMenuLoginLabel || 'Log in'

    element.replaceChildren(link)
}

export const refreshAuthMenu = async (element) => {
    if (!(element instanceof HTMLElement) || !element.matches(selector)) {
        return
    }

    const url = element.dataset.deferredFragmentUrl

    if (!url) {
        return
    }

    refreshes.get(element)?.abort()

    const controller = new AbortController()
    refreshes.set(element, controller)

    // A bfcache restore can resurrect the previous user's account menu.
    // Remove it before fetching so a failed refresh can only leave the safe
    // guest fallback, never session-specific markup.
    element.dataset.deferredFragmentLoaded = 'false'
    renderGuestFallback(element)

    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
        })

        if (!response.ok || refreshes.get(element) !== controller) {
            return
        }

        const html = await response.text()

        if (refreshes.get(element) !== controller) {
            return
        }

        element.innerHTML = html
        element.dataset.deferredFragmentLoaded = 'true'
        element.dispatchEvent(
            new CustomEvent('capell:content-ready', { bubbles: true }),
        )
    } catch (error) {
        if (error?.name !== 'AbortError') {
            console.error('Unable to refresh the authentication menu.', error)
        }
    } finally {
        if (refreshes.get(element) === controller) {
            refreshes.delete(element)
        }
    }
}

const refreshAuthMenusOnPageShow = (event) => {
    if (!event.persisted) {
        return
    }

    document.querySelectorAll(selector).forEach((element) => {
        void refreshAuthMenu(element)
    })
}

if (typeof window !== 'undefined') {
    window.addEventListener('pageshow', refreshAuthMenusOnPageShow)
}
