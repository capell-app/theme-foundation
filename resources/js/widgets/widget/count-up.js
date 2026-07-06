/**
 * count-up.js — v1.0.0
 *
 * Shared Foundation JS module suite (Wave 2.6). Animates a number from 0 up
 * to a target value once the element scrolls into view, then disconnects
 * its observer (animates only once). Themes opt in by rendering the markup
 * and importing this module; nothing runs unless a `[data-count-up]`
 * element exists.
 *
 * Public data-attribute contract (on the element that displays the number,
 * typically paired with `<x-count-up-stat>`):
 * - `data-count-up` (required): the target numeric value, e.g. "4200".
 * - `data-count-up-duration`: animation duration in ms, default 1200.
 * - `data-count-up-locale`: BCP 47 locale tag passed to `Intl.NumberFormat`,
 *   default is the browser default locale (`undefined`).
 * - `data-count-up-style`: forwarded as `Intl.NumberFormat`'s `style`
 *   option (e.g. `"decimal"`, `"percent"`, `"currency"`); default
 *   `"decimal"`.
 * - `data-count-up-currency`: required when `data-count-up-style` is
 *   `"currency"`.
 * - `data-count-up-prefix` / `data-count-up-suffix`: literal strings
 *   rendered before/after the formatted number (e.g. "+", "%").
 * - `data-count-up-decimals`: forwarded as `minimumFractionDigits` and
 *   `maximumFractionDigits`; default 0.
 *
 * Reduced motion: when `prefers-reduced-motion: reduce` matches, the
 * element jumps straight to the fully formatted target value with no
 * animation frames at all.
 *
 * Visibility: uses `IntersectionObserver` (threshold 0.4) so the count
 * animates only after entering the viewport, and only once per element —
 * the observer disconnects immediately after the first intersection.
 */

const countUpObservers = new WeakSet()

function prefersReducedMotion() {
    return (
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    )
}

function buildFormatter(node) {
    const locale = node.getAttribute('data-count-up-locale') || undefined
    const numberStyle = node.getAttribute('data-count-up-style') || 'decimal'
    const currency = node.getAttribute('data-count-up-currency') || undefined
    const decimalsAttribute = node.getAttribute('data-count-up-decimals')
    const decimals =
        decimalsAttribute === null ? 0 : Number.parseInt(decimalsAttribute, 10)

    return new Intl.NumberFormat(locale, {
        currency: numberStyle === 'currency' ? currency : undefined,
        maximumFractionDigits: Number.isNaN(decimals) ? 0 : decimals,
        minimumFractionDigits: Number.isNaN(decimals) ? 0 : decimals,
        style: numberStyle,
    })
}

function renderValue(node, formatter, value) {
    const prefix = node.getAttribute('data-count-up-prefix') || ''
    const suffix = node.getAttribute('data-count-up-suffix') || ''

    node.textContent = `${prefix}${formatter.format(value)}${suffix}`
}

function animateCountUp(node) {
    const target = Number.parseFloat(node.getAttribute('data-count-up') ?? '')

    if (Number.isNaN(target)) {
        return
    }

    const formatter = buildFormatter(node)

    if (prefersReducedMotion()) {
        renderValue(node, formatter, target)

        return
    }

    const durationAttribute = node.getAttribute('data-count-up-duration')
    const duration =
        durationAttribute === null
            ? 1200
            : Number.parseInt(durationAttribute, 10)
    const effectiveDuration = Number.isNaN(duration) ? 1200 : duration
    const startTime = performance.now()

    function tick(now) {
        const elapsed = now - startTime
        const progress = Math.min(elapsed / effectiveDuration, 1)
        const eased = 1 - (1 - progress) ** 3

        renderValue(node, formatter, target * eased)

        if (progress < 1) {
            window.requestAnimationFrame(tick)
        }
    }

    window.requestAnimationFrame(tick)
}

export function initCountUp(node) {
    if (!(node instanceof HTMLElement) || countUpObservers.has(node)) {
        return
    }

    countUpObservers.add(node)

    const observer = new IntersectionObserver(
        (entries, currentObserver) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return
                }

                animateCountUp(node)
                currentObserver.disconnect()
            })
        },
        { threshold: 0.4 },
    )

    observer.observe(node)
}

export function initAllCountUps(root = document) {
    root.querySelectorAll('[data-count-up]').forEach((node) => {
        initCountUp(node)
    })
}

if (typeof document !== 'undefined') {
    initAllCountUps()

    document.addEventListener('livewire:navigated', () => {
        initAllCountUps()
    })
}
