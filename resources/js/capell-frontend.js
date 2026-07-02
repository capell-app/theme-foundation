import AlpineFloatingUI from '@awcodes/alpine-floating-ui'
import Tooltip from '@ryangjchandler/alpine-tooltip'

import './utilities/lightbox'
import './widgets/widget/carousel'

const inactiveFaqTabClasses = [
    'border',
    'border-stone-200',
    'bg-white',
    'text-gray-600',
]

const setFaqTabState = (tab, isActive) => {
    tab.classList.toggle('bg-stone-800', isActive)
    tab.classList.toggle('text-white', isActive)
    tab.classList.toggle('border-transparent', isActive)

    inactiveFaqTabClasses.forEach((className) => {
        tab.classList.toggle(className, !isActive)
    })
}

const filterFaqCategory = (button) => {
    const category = button.dataset.category
    const section = button.closest('.capell-modern-faq-section') ?? document

    section.querySelectorAll('[data-faq-category-tab]').forEach((tab) => {
        setFaqTabState(tab, tab.dataset.category === category)
    })

    section.querySelectorAll('.faq-item').forEach((item) => {
        const matches = category === 'all' || item.dataset.category === category

        item.hidden = !matches
        item.classList.toggle('is-visible', matches)
    })
}

const updatePricingPlan = (plan, billing) => {
    const priceWidget = plan.querySelector('.plan-price')
    const periodWidget = plan.querySelector('.billing-period')
    const price =
        billing === 'annual'
            ? plan.dataset.priceAnnual
            : plan.dataset.priceMonthly

    if (!priceWidget || !price) {
        return
    }

    priceWidget.textContent = priceWidget.textContent.replace(
        billing === 'annual'
            ? plan.dataset.priceMonthly
            : plan.dataset.priceAnnual,
        price,
    )

    if (periodWidget && price !== 'Custom') {
        periodWidget.textContent = billing === 'annual' ? '/year' : '/month'
    }
}

const toggleBillingCycle = (button) => {
    const section = button.closest('.capell-modern-pricing-table') ?? document
    const grid = section.querySelector('.pricing-grid')

    if (!grid) {
        return
    }

    const billing = grid.dataset.billing === 'monthly' ? 'annual' : 'monthly'

    grid.dataset.billing = billing
    button.classList.toggle('is-annual', billing === 'annual')
    grid.querySelectorAll('.pricing-plan').forEach((plan) =>
        updatePricingPlan(plan, billing),
    )
}

const updateCarouselDots = (carousel, activeIndex) => {
    carousel.querySelectorAll('.carousel-dot').forEach((dot, index) => {
        const isActive = index === activeIndex

        dot.classList.toggle('is-active', isActive)
        dot.classList.toggle('bg-stone-900', isActive)
        dot.classList.toggle('bg-stone-300', !isActive)
    })
}

const goToCarouselSlide = (carousel, slideIndex) => {
    const container = carousel.querySelector('.carousel-container')

    if (!container) {
        return
    }

    container.style.transform = `translateX(${-slideIndex * 100}%)`
    updateCarouselDots(carousel, slideIndex)
}

const slideCarousel = (button) => {
    const carousel = button.closest('.layout-builder-testimonials-carousel')
    const container = carousel?.querySelector('.carousel-container')
    const slides = carousel?.querySelectorAll('.carousel-slide') ?? []

    if (!carousel || !container || slides.length === 0) {
        return
    }

    const currentOffset =
        parseInt(
            container.style.transform?.replace('translateX(', '') ?? '0',
        ) || 0
    const currentIndex = Math.round(-currentOffset / 100)
    const direction = Number(button.dataset.carouselDirection ?? 0)
    const nextIndex = (currentIndex + direction + slides.length) % slides.length

    goToCarouselSlide(carousel, nextIndex)
}

const setSpotlightPanel = (spotlight, activeIndex) => {
    spotlight.querySelectorAll('[data-spotlight-tab]').forEach((tab) => {
        const isActive = Number(tab.dataset.spotlightIndex ?? 0) === activeIndex

        tab.dataset.active = isActive ? 'true' : 'false'
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false')
        tab.tabIndex = isActive ? 0 : -1
    })

    spotlight.querySelectorAll('[data-spotlight-panel]').forEach((panel) => {
        panel.hidden = Number(panel.dataset.spotlightIndex ?? 0) !== activeIndex
    })
}

const activateSpotlightTab = (tab) => {
    const spotlight = tab.closest('[data-theme-spotlight]')

    if (!spotlight) {
        return
    }

    setSpotlightPanel(spotlight, Number(tab.dataset.spotlightIndex ?? 0))
}

const moveSpotlightTabFocus = (tab, direction) => {
    const spotlight = tab.closest('[data-theme-spotlight]')
    const tabs = Array.from(
        spotlight?.querySelectorAll('[data-spotlight-tab]') ?? [],
    )

    if (tabs.length === 0) {
        return
    }

    const currentIndex = tabs.indexOf(tab)
    const nextIndex = (currentIndex + direction + tabs.length) % tabs.length
    const nextTab = tabs[nextIndex]

    nextTab.focus()
    activateSpotlightTab(nextTab)
}

const themeCarouselScrollStep = (track) =>
    Math.max(230, Math.floor(track.clientWidth * 0.82))

const updateThemeCarouselButtons = (carousel) => {
    const track = carousel.querySelector('[data-carousel-track]')
    const prev = carousel.querySelector('[data-carousel-prev]')
    const next = carousel.querySelector('[data-carousel-next]')

    if (!track || !prev || !next) {
        return
    }

    const canScroll = track.scrollWidth > track.clientWidth + 1

    prev.classList.toggle('hidden', !canScroll || track.scrollLeft <= 2)
    next.classList.toggle(
        'hidden',
        !canScroll ||
            track.scrollLeft >= track.scrollWidth - track.clientWidth - 2,
    )
}

const initThemeCarousels = (root = document) => {
    root.querySelectorAll('[data-carousel]').forEach((carousel) => {
        if (carousel.dataset.themeCarouselInitialized === 'true') {
            updateThemeCarouselButtons(carousel)

            return
        }

        const track = carousel.querySelector('[data-carousel-track]')
        const prev = carousel.querySelector('[data-carousel-prev]')
        const next = carousel.querySelector('[data-carousel-next]')

        if (!track || !prev || !next) {
            return
        }

        prev.addEventListener('click', () =>
            track.scrollBy({
                left: -themeCarouselScrollStep(track),
                behavior: 'smooth',
            }),
        )
        next.addEventListener('click', () =>
            track.scrollBy({
                left: themeCarouselScrollStep(track),
                behavior: 'smooth',
            }),
        )
        track.addEventListener(
            'scroll',
            () => updateThemeCarouselButtons(carousel),
            { passive: true },
        )
        window.addEventListener('resize', () =>
            updateThemeCarouselButtons(carousel),
        )

        carousel.dataset.themeCarouselInitialized = 'true'
        updateThemeCarouselButtons(carousel)
    })
}

const setPathwayPanel = (pathways, activePanel) => {
    pathways.querySelectorAll('[data-pathway-panel]').forEach((panel) => {
        const isActive = panel === activePanel

        if (!isActive && panel.open) {
            panel.open = false
        }

        panel.dataset.active = isActive && panel.open ? 'true' : 'false'
    })
}

const initPathways = (root = document) => {
    root.querySelectorAll('[data-theme-pathways]').forEach((pathways) => {
        if (pathways.dataset.initialized === 'true') {
            return
        }

        const panels = Array.from(
            pathways.querySelectorAll('[data-pathway-panel]'),
        )
        const openPanel = panels.find((panel) => panel.open) ?? panels[0]

        if (openPanel) {
            openPanel.open = true
            setPathwayPanel(pathways, openPanel)
        }

        panels.forEach((panel) => {
            panel.addEventListener('toggle', () => {
                if (panel.open) {
                    setPathwayPanel(pathways, panel)
                } else {
                    panel.dataset.active = 'false'
                }
            })
        })

        pathways.dataset.initialized = 'true'
    })
}

const initSpotlights = (root = document) => {
    root.querySelectorAll('[data-theme-spotlight]').forEach((spotlight) => {
        if (spotlight.dataset.initialized === 'true') {
            return
        }

        const activeTab =
            spotlight.querySelector(
                '[data-spotlight-tab][aria-selected="true"]',
            ) ?? spotlight.querySelector('[data-spotlight-tab]')

        if (activeTab) {
            setSpotlightPanel(
                spotlight,
                Number(activeTab.dataset.spotlightIndex ?? 0),
            )
        }

        spotlight.dataset.initialized = 'true'
    })
}

document.addEventListener('click', (event) => {
    const faqButton = event.target.closest('[data-faq-category-tab]')

    if (faqButton) {
        filterFaqCategory(faqButton)

        return
    }

    const billingButton = event.target.closest('[data-billing-toggle]')

    if (billingButton) {
        toggleBillingCycle(billingButton)

        return
    }

    const carouselButton = event.target.closest('[data-carousel-direction]')

    if (carouselButton) {
        slideCarousel(carouselButton)

        return
    }

    const carouselDot = event.target.closest('[data-carousel-slide]')

    if (carouselDot) {
        const carousel = carouselDot.closest(
            '.layout-builder-testimonials-carousel',
        )

        if (carousel) {
            goToCarouselSlide(
                carousel,
                Number(carouselDot.dataset.carouselSlide ?? 0),
            )
        }

        return
    }

    const spotlightTab = event.target.closest('[data-spotlight-tab]')

    if (spotlightTab) {
        activateSpotlightTab(spotlightTab)
    }
})

document.addEventListener('keydown', (event) => {
    const spotlightTab = event.target.closest('[data-spotlight-tab]')

    if (
        !spotlightTab ||
        !['ArrowDown', 'ArrowRight', 'ArrowUp', 'ArrowLeft'].includes(event.key)
    ) {
        return
    }

    event.preventDefault()
    moveSpotlightTabFocus(
        spotlightTab,
        ['ArrowDown', 'ArrowRight'].includes(event.key) ? 1 : -1,
    )
})

if (typeof document !== 'undefined') {
    initThemeCarousels()
    initPathways()
    initSpotlights()

    document.addEventListener('livewire:navigated', () => {
        initThemeCarousels()
        initPathways()
        initSpotlights()
    })
}

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(Tooltip)
    window.Alpine.plugin(AlpineFloatingUI)
})
