/**
 * hover-video-poster.js — v1.0.0
 *
 * Shared Foundation JS module suite (Wave 2.6). Companion script for the
 * `<x-capell-theme-foundation::display.hover-video-poster>` Blade primitive
 * (Wave 2.7). Desktop hover is handled entirely by CSS (`:hover`); this
 * module only wires the tap-to-play affordance for touch devices, since
 * there is no CSS-only way to gate "tap the overlay button, then play"
 * behaviour. Themes opt in by importing this module; nothing runs unless a
 * `[data-hover-video-poster]` container exists.
 *
 * Expected markup contract: a `[data-hover-video-poster]` container with a
 * `[data-hover-video-poster-video]` `<video>` element (always `muted`) and
 * a `[data-hover-video-poster-trigger]` button. Tapping the trigger adds
 * the `hover-video-poster-active` class to the container (the component's
 * CSS reveals/plays the video while that class is present) and calls
 * `video.play()`; tapping again (or the video ending, if not looping)
 * reverts to the poster.
 *
 * Reduced motion: when `prefers-reduced-motion: reduce` matches, this
 * module does not attach any handlers at all, so the video can never be
 * triggered and only the static poster ever renders — matching the
 * component's own CSS-level reduced-motion guard.
 */

function prefersReducedMotion() {
    return (
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    )
}

export function initHoverVideoPoster(container) {
    if (!(container instanceof HTMLElement) || prefersReducedMotion()) {
        return
    }

    const video = container.querySelector('[data-hover-video-poster-video]')
    const trigger = container.querySelector('[data-hover-video-poster-trigger]')

    if (
        !(video instanceof HTMLVideoElement) ||
        !(trigger instanceof HTMLElement)
    ) {
        return
    }

    trigger.addEventListener('click', () => {
        const isActive = container.classList.toggle('hover-video-poster-active')

        if (isActive) {
            video.currentTime = 0
            video.play().catch(() => {})
        } else {
            video.pause()
        }
    })

    video.addEventListener('mouseleave', () => {
        container.classList.remove('hover-video-poster-active')
        video.pause()
    })
}

export function initAllHoverVideoPosters(root = document) {
    root.querySelectorAll('[data-hover-video-poster]').forEach((container) => {
        initHoverVideoPoster(container)
    })
}

if (typeof document !== 'undefined') {
    initAllHoverVideoPosters()

    document.addEventListener('livewire:navigated', () => {
        initAllHoverVideoPosters()
    })
}
