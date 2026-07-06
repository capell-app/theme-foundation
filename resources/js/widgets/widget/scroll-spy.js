/**
 * scroll-spy.js — v1.0.0
 *
 * Shared Foundation JS module suite (Wave 2.6). Highlights the nav link
 * matching the section currently in view. Feature-detects CSS
 * `scroll-timeline`/`view-timeline` support first and prefers a CSS-only
 * path when available (this module only supplies the `aria-current`
 * bookkeeping either way, since `view-timeline` alone cannot set ARIA
 * state); falls back to `IntersectionObserver` where scroll-driven
 * animations are unsupported. Themes opt in by rendering the markup and
 * importing this module; nothing runs unless a `[data-scroll-spy]`
 * container exists.
 *
 * Public data-attribute contract:
 * - `[data-scroll-spy]` on the nav container. Its links must be anchors
 *   whose `href` is a same-page fragment (`#section-id`) matching a
 *   `data-scroll-spy-target` (or `id`) on the corresponding section.
 * - `data-scroll-spy-offset` (optional, on the container): pixel offset
 *   applied to the `IntersectionObserver` root margin, useful to account
 *   for a sticky header. Default 0.
 *
 * Behaviour: exactly one link carries `aria-current="true"` at a time
 * (removed from all others first). When CSS `animation-timeline: view()`
 * is supported (`CSS.supports('animation-timeline: view()')`), this module
 * still uses IntersectionObserver for the `aria-current` bookkeeping (CSS
 * alone has no accessibility-tree hook), but adds a
 * `data-scroll-spy-css-driven` attribute to the container so a theme's CSS
 * can layer in a pure-CSS visual indicator (e.g. an underline driven by
 * `view-timeline`) without JS re-implementing the visual effect —
 * fallback themes without that CSS still get a fully working indicator
 * from the `aria-current` attribute alone.
 *
 * Reduced motion: this module has no animation of its own; any visual
 * transition is theme CSS, which must honour `prefers-reduced-motion`
 * itself per guardrail 6.
 */

function supportsScrollDrivenAnimations() {
    return (
        typeof CSS !== 'undefined' &&
        typeof CSS.supports === 'function' &&
        CSS.supports('animation-timeline: view()')
    )
}

function resolveSections(container, links) {
    return links.map((linkNode) => {
        const targetId = (linkNode.getAttribute('href') || '').replace(/^#/, '')

        if (targetId === '') {
            return null
        }

        return (
            document.getElementById(targetId) ??
            document.querySelector(`[data-scroll-spy-target="${targetId}"]`)
        )
    })
}

function setActiveLink(links, activeLink) {
    links.forEach((linkNode) => {
        if (linkNode === activeLink) {
            linkNode.setAttribute('aria-current', 'true')
        } else {
            linkNode.removeAttribute('aria-current')
        }
    })
}

export function destroyScrollSpy(container) {
    const observer = container.__foundationScrollSpyObserver

    if (observer) {
        observer.disconnect()
        delete container.__foundationScrollSpyObserver
    }
}

export function initScrollSpy(container) {
    if (!(container instanceof HTMLElement)) {
        return
    }

    destroyScrollSpy(container)

    const links = Array.from(container.querySelectorAll('a[href^="#"]'))

    if (links.length === 0) {
        return
    }

    const sections = resolveSections(container, links)

    if (supportsScrollDrivenAnimations()) {
        container.setAttribute('data-scroll-spy-css-driven', 'true')
    }

    const offsetAttribute = container.getAttribute('data-scroll-spy-offset')
    const offset =
        offsetAttribute === null ? 0 : Number.parseInt(offsetAttribute, 10)
    const rootMargin = `-${Number.isNaN(offset) ? 0 : offset}px 0px -60% 0px`

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return
                }

                const sectionIndex = sections.indexOf(entry.target)

                if (sectionIndex === -1) {
                    return
                }

                setActiveLink(links, links[sectionIndex])
            })
        },
        { rootMargin, threshold: 0 },
    )

    sections.forEach((sectionNode) => {
        if (sectionNode) {
            observer.observe(sectionNode)
        }
    })

    container.__foundationScrollSpyObserver = observer
}

export function initAllScrollSpies(root = document) {
    root.querySelectorAll('[data-scroll-spy]').forEach((container) => {
        initScrollSpy(container)
    })
}

if (typeof document !== 'undefined') {
    initAllScrollSpies()

    document.addEventListener('livewire:navigated', () => {
        initAllScrollSpies()
    })
}
