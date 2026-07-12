/**
 * tabs.js — v1.0.0
 *
 * Shared Foundation JS module suite (Wave 2.6). ARIA tabs pattern. Themes
 * opt in by rendering the markup and importing this module explicitly —
 * nothing here runs unless a `[role="tablist"]` exists in the page.
 *
 * Expected markup contract:
 * - A container `[role="tablist"]`, optionally `data-tabs-orientation`
 *   (`"horizontal"` default, or `"vertical"` to use Up/Down instead of
 *   Left/Right for arrow navigation).
 * - Children with `role="tab"`, each with a unique `id`, `aria-controls`
 *   pointing at its panel's `id`, and `tabindex` managed by this module
 *   (only the active tab is `tabindex="0"`; the rest are `tabindex="-1"`).
 * - Panels with `role="tabpanel"`, `aria-labelledby` pointing back at the
 *   owning tab's `id`.
 * - `data-tabs-active-index` (optional, on the tablist) sets the initially
 *   selected tab index; defaults to 0, or the first tab already carrying
 *   `aria-selected="true"`.
 *
 * Keyboard support: Left/Right (or Up/Down when vertical) move focus and
 * activate the adjacent tab, wrapping at the ends; Home/End jump to the
 * first/last tab. Activation follows focus (standard "automatic
 * activation" tabs pattern).
 *
 * State management: exactly one tab carries `aria-selected="true"` at a
 * time; its panel is shown (`hidden` attribute removed) and all others are
 * hidden. Dispatches a `tabs-change` CustomEvent (bubbles) on the tablist
 * with `detail: { tabId, panelId, index }` whenever the active tab changes.
 *
 * Reduced motion: this module has no animation of its own (panel
 * visibility toggles instantly); it exists purely for state/ARIA
 * management, so `prefers-reduced-motion` has nothing to gate here.
 */

const tabsStore = new WeakMap()

function readOrientation(tablistNode) {
    const value = tablistNode.getAttribute('data-tabs-orientation')

    return value === 'vertical' ? 'vertical' : 'horizontal'
}

function readInitialIndex(tablistNode, tabs) {
    const attributeValue = tablistNode.getAttribute('data-tabs-active-index')

    if (attributeValue !== null) {
        const parsedIndex = Number.parseInt(attributeValue, 10)

        if (
            !Number.isNaN(parsedIndex) &&
            parsedIndex >= 0 &&
            parsedIndex < tabs.length
        ) {
            return parsedIndex
        }
    }

    const preselectedIndex = tabs.findIndex(
        (tabNode) => tabNode.getAttribute('aria-selected') === 'true',
    )

    return preselectedIndex === -1 ? 0 : preselectedIndex
}

function activateTab(tablistNode, tabs, panels, index, options = {}) {
    const { moveFocus = true } = options
    const targetIndex = ((index % tabs.length) + tabs.length) % tabs.length

    tabs.forEach((tabNode, tabIndex) => {
        const isActive = tabIndex === targetIndex

        tabNode.setAttribute('aria-selected', isActive ? 'true' : 'false')
        tabNode.setAttribute('tabindex', isActive ? '0' : '-1')
    })

    panels.forEach((panelNode, panelIndex) => {
        if (!panelNode) {
            return
        }

        if (panelIndex === targetIndex) {
            panelNode.removeAttribute('hidden')
        } else {
            panelNode.setAttribute('hidden', '')
        }
    })

    if (moveFocus) {
        tabs[targetIndex].focus()
    }

    tablistNode.dispatchEvent(
        new CustomEvent('tabs-change', {
            bubbles: true,
            detail: {
                index: targetIndex,
                panelId: panels[targetIndex]?.id ?? null,
                tabId: tabs[targetIndex].id,
            },
        }),
    )
}

function resolvePanel(tabNode) {
    const panelId = tabNode.getAttribute('aria-controls')

    return panelId ? document.getElementById(panelId) : null
}

export function destroyTabs(tablistNode) {
    const entry = tabsStore.get(tablistNode)

    if (!entry) {
        return
    }

    entry.abortController.abort()
    tabsStore.delete(tablistNode)
}

export function initTabs(tablistNode) {
    if (!(tablistNode instanceof HTMLElement)) {
        return
    }

    destroyTabs(tablistNode)

    const tabs = Array.from(tablistNode.querySelectorAll('[role="tab"]'))

    if (tabs.length === 0) {
        return
    }

    const panels = tabs.map((tabNode) => resolvePanel(tabNode))
    const orientation = readOrientation(tablistNode)
    const abortController = new AbortController()
    const { signal } = abortController
    const initialIndex = readInitialIndex(tablistNode, tabs)

    activateTab(tablistNode, tabs, panels, initialIndex, { moveFocus: false })

    tabs.forEach((tabNode, index) => {
        tabNode.addEventListener(
            'click',
            (event) => {
                event.preventDefault()
                activateTab(tablistNode, tabs, panels, index)
            },
            { signal },
        )

        tabNode.addEventListener(
            'keydown',
            (event) => {
                const previousKey =
                    orientation === 'vertical' ? 'ArrowUp' : 'ArrowLeft'
                const nextKey =
                    orientation === 'vertical' ? 'ArrowDown' : 'ArrowRight'

                if (event.key === previousKey) {
                    event.preventDefault()
                    activateTab(tablistNode, tabs, panels, index - 1)

                    return
                }

                if (event.key === nextKey) {
                    event.preventDefault()
                    activateTab(tablistNode, tabs, panels, index + 1)

                    return
                }

                if (event.key === 'Home') {
                    event.preventDefault()
                    activateTab(tablistNode, tabs, panels, 0)

                    return
                }

                if (event.key === 'End') {
                    event.preventDefault()
                    activateTab(tablistNode, tabs, panels, tabs.length - 1)
                }
            },
            { signal },
        )
    })

    tabsStore.set(tablistNode, { abortController })
}

export function initAllTabs(root = document) {
    root.querySelectorAll('[role="tablist"]').forEach((tablistNode) => {
        initTabs(tablistNode)
    })
}

if (typeof document !== 'undefined') {
    initAllTabs()

    document.addEventListener('livewire:navigated', () => {
        initAllTabs()
    })
}
