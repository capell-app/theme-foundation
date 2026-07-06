/**
 * accordion-toggle.js — v1.0.0
 *
 * Shared Foundation JS module suite (Wave 2.6). Accessible accordion.
 * Themes opt in by rendering the markup and importing this module;
 * nothing runs unless a `[data-accordion]` container exists.
 *
 * Expected markup contract:
 * - Container `[data-accordion]`, optionally `data-accordion-mode`
 *   (`"single"` default — opening one item closes the others in the same
 *   container — or `"multi"` — items open/close independently).
 * - Each item's trigger is a `button[data-accordion-trigger]` with
 *   `aria-controls` pointing at its panel's `id`; this module manages
 *   `aria-expanded` on the trigger and the `hidden` attribute on the panel.
 * - Each panel is `[data-accordion-panel]` with a unique `id` matching a
 *   trigger's `aria-controls`.
 * - `data-accordion-trigger` may carry `aria-expanded="true"` in the
 *   initial markup to start that item open; otherwise all items start
 *   closed.
 *
 * Keyboard: triggers are native `<button>` elements, so Enter/Space
 * activation is free; this module adds no extra keyboard handling beyond
 * the click handler shared with mouse/touch activation.
 *
 * Reduced motion: this module toggles the `hidden` attribute instantly
 * with no animation of its own; a theme's CSS may layer in an
 * expand/collapse transition, which must honour `prefers-reduced-motion`
 * per guardrail 6.
 */

function readMode(container) {
    return container.getAttribute('data-accordion-mode') === 'multi'
        ? 'multi'
        : 'single'
}

function setItemState(trigger, panel, expanded) {
    trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false')

    if (expanded) {
        panel.removeAttribute('hidden')
    } else {
        panel.setAttribute('hidden', '')
    }
}

function resolvePanel(trigger) {
    const panelId = trigger.getAttribute('aria-controls')

    return panelId ? document.getElementById(panelId) : null
}

export function destroyAccordion(container) {
    const abortController = container.__foundationAccordionAbortController

    if (abortController) {
        abortController.abort()
        delete container.__foundationAccordionAbortController
    }
}

export function initAccordion(container) {
    if (!(container instanceof HTMLElement)) {
        return
    }

    destroyAccordion(container)

    const triggers = Array.from(
        container.querySelectorAll('[data-accordion-trigger]'),
    )

    if (triggers.length === 0) {
        return
    }

    const mode = readMode(container)
    const abortController = new AbortController()
    const { signal } = abortController
    const items = triggers
        .map((trigger) => ({ panel: resolvePanel(trigger), trigger }))
        .filter((item) => item.panel !== null)

    items.forEach(({ panel, trigger }) => {
        const startsExpanded = trigger.getAttribute('aria-expanded') === 'true'

        setItemState(trigger, panel, startsExpanded)
    })

    items.forEach(({ panel, trigger }) => {
        trigger.addEventListener(
            'click',
            () => {
                const isExpanded =
                    trigger.getAttribute('aria-expanded') === 'true'

                if (mode === 'single' && !isExpanded) {
                    items.forEach((otherItem) => {
                        if (otherItem.trigger !== trigger) {
                            setItemState(
                                otherItem.trigger,
                                otherItem.panel,
                                false,
                            )
                        }
                    })
                }

                setItemState(trigger, panel, !isExpanded)
            },
            { signal },
        )
    })

    container.__foundationAccordionAbortController = abortController
}

export function initAllAccordions(root = document) {
    root.querySelectorAll('[data-accordion]').forEach((container) => {
        initAccordion(container)
    })
}

if (typeof document !== 'undefined') {
    initAllAccordions()

    document.addEventListener('livewire:navigated', () => {
        initAllAccordions()
    })
}
