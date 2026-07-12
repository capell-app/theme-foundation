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
 *   tier array (`{label, price, features[], ctaLabel, ctaUrl}[]`)
 * - `[data-pricing-spectrum-label]` / `-price]` / `-features]` / `-cta]`:
 *   the display panel nodes updated as the slider moves
 * - radio inputs named `pricing-spectrum-tier*`: the no-JS/discrete-tier
 *   fallback, kept in sync with the slider in both directions
 */

function readTierData(root) {
    const template = root.querySelector('[data-pricing-spectrum-tier-data]')

    if (!template) {
        return []
    }

    try {
        const parsed = JSON.parse(template.textContent?.trim() ?? '[]')

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

        features.innerHTML = featureList
            .map(
                (feature) =>
                    `<li class="flex items-center gap-2"><span class="font-bold text-emerald-600" aria-hidden="true">&check;</span><span>${String(feature)}</span></li>`,
            )
            .join('')
    }

    if (cta && tier?.ctaLabel) {
        cta.textContent = tier.ctaLabel
        cta.setAttribute('href', tier.ctaUrl ?? '#')
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
