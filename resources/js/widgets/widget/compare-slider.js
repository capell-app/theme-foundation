/**
 * compare-slider.js — v1.0.0
 *
 * Shared Foundation JS module suite (Wave 2.6). Before/after image compare
 * slider. Themes opt in by rendering the markup and importing this module;
 * nothing runs unless a `[data-compare-slider]` container exists.
 *
 * Expected markup contract:
 * - Container `[data-compare-slider]` holding a "before" layer and an
 *   "after" layer clipped by position (theme CSS owns the visual clip —
 *   this module only writes `--compare-slider-position` as a percentage
 *   custom property on the container, e.g.
 *   `clip-path: inset(0 calc(100% - var(--compare-slider-position)) 0 0)`
 *   on the "after" layer).
 * - A handle element `[data-compare-slider-handle]` inside the container,
 *   given `role="slider"`, `tabindex="0"`, `aria-valuemin="0"`,
 *   `aria-valuemax="100"`, and `aria-valuenow` (kept in sync by this
 *   module). `aria-label` / `aria-orientation="horizontal"` should be set
 *   in the Blade markup.
 * - `data-compare-slider-initial` (optional, on the container): starting
 *   position 0-100, default 50.
 * - `data-compare-slider-step` (optional, on the container): arrow-key step
 *   size in percentage points, default 5.
 *
 * Keyboard: Left/Right (or Down/Up) adjust position by the step size;
 * Home/End jump to 0/100.
 *
 * Pointer: drag support via Pointer Events (`pointerdown`/`pointermove`/
 * `pointerup`) on the handle, covering mouse, touch, and pen through one
 * code path; `setPointerCapture` keeps the drag tracking even if the
 * pointer leaves the handle bounds mid-drag.
 *
 * Reduced motion: position updates are immediate attribute/style writes
 * with no transition applied by this module; any CSS transition a theme
 * layers on top of `--compare-slider-position` must itself honour
 * `prefers-reduced-motion` per guardrail 6.
 */

function clampPosition(value) {
    return Math.min(100, Math.max(0, value))
}

function applyPosition(container, handle, position) {
    const clamped = clampPosition(position)

    container.style.setProperty('--compare-slider-position', `${clamped}%`)
    handle.setAttribute('aria-valuenow', String(Math.round(clamped)))
}

function positionFromPointer(container, clientX) {
    const bounds = container.getBoundingClientRect()

    if (bounds.width === 0) {
        return 50
    }

    return clampPosition(((clientX - bounds.left) / bounds.width) * 100)
}

export function destroyCompareSlider(container) {
    const abortController = container.__foundationCompareSliderAbortController

    if (abortController) {
        abortController.abort()
        delete container.__foundationCompareSliderAbortController
    }
}

export function initCompareSlider(container) {
    if (!(container instanceof HTMLElement)) {
        return
    }

    const handle = container.querySelector('[data-compare-slider-handle]')

    if (!(handle instanceof HTMLElement)) {
        return
    }

    destroyCompareSlider(container)

    const abortController = new AbortController()
    const { signal } = abortController

    const initialAttribute = container.getAttribute(
        'data-compare-slider-initial',
    )
    const initialPosition =
        initialAttribute === null ? 50 : Number.parseFloat(initialAttribute)

    const stepAttribute = container.getAttribute('data-compare-slider-step')
    const step = stepAttribute === null ? 5 : Number.parseFloat(stepAttribute)
    const effectiveStep = Number.isNaN(step) ? 5 : step

    handle.setAttribute('role', 'slider')
    handle.setAttribute('aria-valuemin', '0')
    handle.setAttribute('aria-valuemax', '100')

    if (!handle.hasAttribute('tabindex')) {
        handle.setAttribute('tabindex', '0')
    }

    applyPosition(
        container,
        handle,
        Number.isNaN(initialPosition) ? 50 : initialPosition,
    )

    handle.addEventListener(
        'keydown',
        (event) => {
            const currentPosition = Number.parseFloat(
                handle.getAttribute('aria-valuenow') ?? '50',
            )

            if (['ArrowLeft', 'ArrowDown'].includes(event.key)) {
                event.preventDefault()
                applyPosition(
                    container,
                    handle,
                    currentPosition - effectiveStep,
                )

                return
            }

            if (['ArrowRight', 'ArrowUp'].includes(event.key)) {
                event.preventDefault()
                applyPosition(
                    container,
                    handle,
                    currentPosition + effectiveStep,
                )

                return
            }

            if (event.key === 'Home') {
                event.preventDefault()
                applyPosition(container, handle, 0)

                return
            }

            if (event.key === 'End') {
                event.preventDefault()
                applyPosition(container, handle, 100)
            }
        },
        { signal },
    )

    handle.addEventListener(
        'pointerdown',
        (event) => {
            handle.setPointerCapture(event.pointerId)
            applyPosition(
                container,
                handle,
                positionFromPointer(container, event.clientX),
            )
        },
        { signal },
    )

    handle.addEventListener(
        'pointermove',
        (event) => {
            if (!handle.hasPointerCapture(event.pointerId)) {
                return
            }

            applyPosition(
                container,
                handle,
                positionFromPointer(container, event.clientX),
            )
        },
        { signal },
    )

    handle.addEventListener(
        'pointerup',
        (event) => {
            if (handle.hasPointerCapture(event.pointerId)) {
                handle.releasePointerCapture(event.pointerId)
            }
        },
        { signal },
    )

    container.__foundationCompareSliderAbortController = abortController
}

export function initAllCompareSliders(root = document) {
    root.querySelectorAll('[data-compare-slider]').forEach((container) => {
        initCompareSlider(container)
    })
}

if (typeof document !== 'undefined') {
    initAllCompareSliders()

    document.addEventListener('livewire:navigated', () => {
        initAllCompareSliders()
    })
}
