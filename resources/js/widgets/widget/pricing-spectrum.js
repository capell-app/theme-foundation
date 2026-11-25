/**
 * pricing-spectrum.js — v1.0.0
 *
 * Wave 4c §D. Syncs the `pricing-value-spectrum` section's range slider (or
 * its `:has(:checked)` radio-group fallback) to the tier display panel.
 * Payload-driven only: tier data is read from the `<template
 * data-pricing-spectrum-tier-data>` JSON blob the Blade view emits, so this
 * module never fetches pricing from a live service.
 *
 * Public data-attribute contract:
 * - `[data-pricing-spectrum]` (section root)
 * - `[data-pricing-spectrum-slider]`: the `<input type="range">`
 * - `[data-pricing-spectrum-tier-data]`: a `<template>` holding the JSON
 *   tier array (`{label, price, features[], ctaLabel, ctaUrl}[]`); `ctaUrl`
 *   is already sanitised by the view and is null when the tier has no link
 * - `[data-pricing-spectrum-label]` / `-price]` / `-features]` / `-cta]`:
 *   the display panel nodes updated as the slider moves; the CTA is hidden
 *   (and loses its `href`) while the active tier has no link
 * - radio inputs named `pricing-spectrum-tier*`: the no-JS/discrete-tier
 *   fallback, kept in sync with the slider in both directions
 */

function readTierData(root) {
    const template = root.querySelector('[data-pricing-spectrum-tier-data]')

    if (!template) {
        return []
    }

    try {
        // A <template> keeps its parsed text in `.content`; its own
        // textContent is always empty.
        const json = (template.content ?? template).textContent?.trim()
        const parsed = JSON.parse(json || '[]')

        return Array.isArray(parsed) ? parsed : []
    } catch {
        return []
    }
}

function renderTier(root, tier) {
    const label = root.querySelector('[data-pricing-spectrum-label]')
    const price = root.querySelector('[data-pricing-spectrum-price]')
    const features = root.querySelector('[data-pricing-spectrum-features]')
    const cta = root.querySelector('[data-pricing-spectrum-cta]')

    if (label) {
        label.textContent = tier?.label ?? ''
    }

    if (price) {
        price.textContent = tier?.price ?? ''
    }

    if (features) {
        const featureList = Array.isArray(tier?.features) ? tier.features : []

        features.replaceChildren(
            ...featureList.map((feature) => {
                const item = document.createElement('li')
                const tick = document.createElement('span')
                const text = document.createElement('span')

                item.className = 'flex items-center gap-2'
                tick.className =
                    'font-bold text-emerald-700 dark:text-emerald-400'
                tick.setAttribute('aria-hidden', 'true')
                tick.textContent = '\u2713'
                text.textContent = String(feature)
                item.append(tick, text)

                return item
            }),
        )
    }

    if (cta) {
        // The server only emits ctaUrl values that passed its public URL
        // sanitiser; a tier without one shows no call to action rather than a
        // link that goes nowhere.
        const ctaUrl = typeof tier?.ctaUrl === 'string' ? tier.ctaUrl : ''
        const ctaLabel = typeof tier?.ctaLabel === 'string' ? tier.ctaLabel : ''

        if (ctaUrl !== '' && ctaLabel !== '') {
            cta.textContent = ctaLabel
            cta.setAttribute('href', ctaUrl)
            cta.hidden = false
        } else {
            cta.removeAttribute('href')
            cta.hidden = true
        }
    }
}

function setActiveTierIndex(root, tiers, index) {
    const clampedIndex = Math.min(
        Math.max(index, 0),
        Math.max(tiers.length - 1, 0),
    )
    const tier = tiers[clampedIndex]

    renderTier(root, tier)

    const slider = root.querySelector('[data-pricing-spectrum-slider]')

    if (slider && Number(slider.value) !== clampedIndex) {
        slider.value = String(clampedIndex)
    }

    root.querySelectorAll('input[name^="pricing-spectrum-tier"]').forEach(
        (radio) => {
            radio.checked = Number(radio.value) === clampedIndex
        },
    )
}

function initPricingSpectrum(root) {
    if (root.dataset.pricingSpectrumInitialized === 'true') {
        return
    }

    const tiers = readTierData(root)
    const slider = root.querySelector('[data-pricing-spectrum-slider]')

    if (tiers.length === 0) {
        return
    }

    slider?.addEventListener('input', () => {
        setActiveTierIndex(root, tiers, Number(slider.value))
    })

    root.querySelectorAll('input[name^="pricing-spectrum-tier"]').forEach(
        (radio) => {
            radio.addEventListener('change', () => {
                if (radio.checked) {
                    setActiveTierIndex(root, tiers, Number(radio.value))
                }
            })
        },
    )

    root.dataset.pricingSpectrumInitialized = 'true'
}

export function initAllPricingSpectrums(root = document) {
    root.querySelectorAll('[data-pricing-spectrum]').forEach((section) => {
        initPricingSpectrum(section)
    })
}

if (typeof document !== 'undefined') {
    initAllPricingSpectrums()

    document.addEventListener('livewire:navigated', () => {
        initAllPricingSpectrums()
    })
}
