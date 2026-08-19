import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'

const swiperInstances = []
const intersectionObserverInstances = []

class IntersectionObserverMock {
    constructor(callback) {
        this.callback = callback
        this.observe = vi.fn()
        this.disconnect = vi.fn()
        intersectionObserverInstances.push(this)
    }
}

globalThis.IntersectionObserver = IntersectionObserverMock

vi.mock('swiper', () => {
    class SwiperMock {
        constructor(node, settings) {
            this.node = node
            this.settings = settings
            this.destroyed = false
            this.realIndex = 0
            this.activeIndex = 0
            this.autoplay = {
                start: vi.fn(),
                stop: vi.fn(),
            }
            this.destroy = vi.fn(() => {
                this.destroyed = true
            })
            this.slideTo = vi.fn()
            this.slideToLoop = vi.fn()
            this.update = vi.fn()

            swiperInstances.push(this)
            settings.on?.init?.call(this)
        }
    }

    return {
        Swiper: SwiperMock,
    }
})

vi.mock('swiper/modules', () => {
    return {
        Autoplay: Symbol('Autoplay'),
        A11y: Symbol('A11y'),
        EffectFade: Symbol('EffectFade'),
        Grid: Symbol('Grid'),
        Keyboard: Symbol('Keyboard'),
        Mousewheel: Symbol('Mousewheel'),
        Navigation: Symbol('Navigation'),
        Pagination: Symbol('Pagination'),
    }
})

let buildSwiperSettings
let destroyCarousel
let initCarousel
let parseCarouselOptions
let resolveCarouselControls

beforeAll(async () => {
    const carouselModule = await import('./carousel.js')

    buildSwiperSettings = carouselModule.buildSwiperSettings
    destroyCarousel = carouselModule.destroyCarousel
    initCarousel = carouselModule.initCarousel
    parseCarouselOptions = carouselModule.parseCarouselOptions
    resolveCarouselControls = carouselModule.resolveCarouselControls
})

function createCarouselMarkup(attributes = '') {
    return `
        <section data-carousel-scope>
            <div class="swiper" ${attributes}>
                <div class="swiper-wrapper">
                    <div class="swiper-slide">One</div>
                    <div class="swiper-slide">Two</div>
                </div>
            </div>
            <div class="swiper-controls">
                <button class="swiper-button-prev" type="button"></button>
                <button class="swiper-button-next" type="button"></button>
                <div class="swiper-pagination"></div>
            </div>
        </section>
    `
}

describe('carousel runtime', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
        document.documentElement.dataset.carouselLabelCarousel = 'Carousel'
        document.documentElement.dataset.carouselLabelGoToSlide =
            'Go to slide :number'
        document.documentElement.dataset.carouselLabelNextSlide = 'Next slide'
        document.documentElement.dataset.carouselLabelPause = 'Pause carousel'
        document.documentElement.dataset.carouselLabelPlay = 'Play carousel'
        document.documentElement.dataset.carouselLabelPreviousSlide =
            'Previous slide'
        document.documentElement.dataset.carouselLabelSlide =
            'Slide {{index}} of {{slidesLength}}'
        swiperInstances.length = 0
        intersectionObserverInstances.length = 0
        vi.clearAllMocks()
    })

    it('disables interaction by default for fade carousels', () => {
        document.body.innerHTML = createCarouselMarkup(
            'data-carousel-effect="fade" data-carousel-id="fade-carousel"',
        )

        const swiperNode = document.querySelector('.swiper')
        const options = parseCarouselOptions(swiperNode)

        expect(options.fadeEnabled).toBe(true)
        expect(options.effect).toBe('fade')
        expect(options.interactionEnabled).toBe(false)
        expect(options.grabCursor).toBe(false)
    })

    it('allows explicit touch overrides for fade carousels', () => {
        document.body.innerHTML = createCarouselMarkup(
            'data-carousel-effect="fade" data-carousel-touch="1" data-carousel-id="override-carousel"',
        )

        const swiperNode = document.querySelector('.swiper')
        const options = parseCarouselOptions(swiperNode)

        expect(options.fadeEnabled).toBe(true)
        expect(options.interactionEnabled).toBe(true)
        expect(options.grabCursor).toBe(true)
    })

    it('does not enable autoplay when the visitor prefers reduced motion', () => {
        const originalMatchMedia = window.matchMedia
        window.matchMedia = vi.fn(() => ({ matches: true }))

        try {
            document.body.innerHTML = createCarouselMarkup(
                'data-carousel-autoplay="1" data-carousel-id="reduced-motion-carousel"',
            )

            const swiperNode = document.querySelector('.swiper')
            const options = parseCarouselOptions(swiperNode)

            expect(options.autoplayRequested).toBe(true)
            expect(options.autoplayEnabled).toBe(false)

            const controls = resolveCarouselControls(swiperNode, options)
            const settings = buildSwiperSettings(
                swiperNode,
                options,
                controls,
                new AbortController().signal,
            )

            expect(settings.autoplay).toBeUndefined()
            expect(settings.modules.map(String)).not.toContain(
                'Symbol(Autoplay)',
            )

            const swiper = initCarousel(swiperNode)

            expect(swiper.settings.autoplay).toBeUndefined()
            expect(
                document.querySelector('[data-carousel-autoplay-toggle]'),
            ).toBeNull()
        } finally {
            window.matchMedia = originalMatchMedia
        }
    })

    it('passes container-based breakpoints through to Swiper', () => {
        document.body.innerHTML = createCarouselMarkup(
            [
                'data-carousel-id="container-carousel"',
                'data-carousel-breakpoints-base="container"',
                'data-carousel-breakpoints=\'{"320":{"slidesPerView":1.1},"520":{"slidesPerView":2},"760":{"slidesPerView":3}}\'',
            ].join(' '),
        )

        const swiperNode = document.querySelector('.swiper')
        const options = parseCarouselOptions(swiperNode)
        const controls = resolveCarouselControls(swiperNode, options)
        const abortController = new AbortController()
        const settings = buildSwiperSettings(
            swiperNode,
            options,
            controls,
            abortController.signal,
        )

        expect(options.breakpointsBase).toBe('container')
        expect(settings.breakpointsBase).toBe('container')
        expect(settings.breakpoints[320].slidesPerView).toBe(1.1)
        expect(settings.breakpoints[520].slidesPerView).toBe(2)
        expect(settings.breakpoints[760].slidesPerView).toBe(3)
    })

    it('resolves controls by scoped carousel id', () => {
        document.body.innerHTML = `
            <section data-carousel-scope>
                <div class="swiper" data-carousel-id="first-carousel">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">One</div>
                    </div>
                </div>
                <div class="swiper-controls" data-carousel-controls="first-carousel">
                    <div class="swiper-pagination first-pagination"></div>
                </div>

                <div class="swiper" data-carousel-id="second-carousel">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">Two</div>
                    </div>
                </div>
                <div class="swiper-controls" data-carousel-controls="second-carousel">
                    <div class="swiper-pagination second-pagination"></div>
                </div>
            </section>
        `

        const swiperNode = document.querySelector(
            '[data-carousel-id="second-carousel"]',
        )
        const controls = resolveCarouselControls(swiperNode)

        expect(controls.dotsNode.classList.contains('second-pagination')).toBe(
            true,
        )
    })

    it('binds pagination clicks via getSlideIndexByData for looped carousels', () => {
        document.body.innerHTML = createCarouselMarkup(
            [
                'data-carousel-id="loop-carousel"',
                'data-carousel-loop="1"',
                'data-carousel-effect="fade"',
                'data-carousel-pagination="1"',
            ].join(' '),
        )

        const swiperNode = document.querySelector('.swiper')
        const options = parseCarouselOptions(swiperNode)
        const controls = resolveCarouselControls(swiperNode, options)
        const abortController = new AbortController()

        controls.dotsNode.innerHTML = [0, 1]
            .map(
                (index) =>
                    `<button type="button" class="swiper-pagination-bullet" data-carousel-bullet-index="${index}"></button>`,
            )
            .join('')

        const settings = buildSwiperSettings(
            swiperNode,
            options,
            controls,
            abortController.signal,
        )
        const swiperInstance = {
            activeIndex: 0,
            destroyed: false,
            realIndex: 1,
            getSlideIndexByData: vi.fn((index) => index + 1),
            slideTo: vi.fn(),
            update: vi.fn(),
        }

        settings.on.init.call(swiperInstance)

        const bullets = controls.dotsNode.querySelectorAll(
            '.swiper-pagination-bullet',
        )

        bullets[1].dispatchEvent(new MouseEvent('click', { bubbles: true }))

        expect(swiperInstance.getSlideIndexByData).toHaveBeenCalledWith(1)
        expect(swiperInstance.slideTo).toHaveBeenCalledOnce()
        expect(swiperInstance.slideTo).toHaveBeenCalledWith(2)
        expect(swiperNode.classList.contains('swiper-ready')).toBe(true)
        expect(
            swiperNode.querySelectorAll('.swiper-slide-selected'),
        ).toHaveLength(1)
    })

    it('cleans up observers and swiper instances on destroy', () => {
        document.body.innerHTML = createCarouselMarkup(
            'data-carousel-id="destroy-carousel" data-carousel-pagination="1"',
        )

        const swiperNode = document.querySelector('.swiper')
        const swiperInstance = initCarousel(swiperNode)

        expect(swiperInstance).not.toBeNull()
        expect(swiperNode.dataset.initialized).toBe('true')
        expect(intersectionObserverInstances).toHaveLength(1)

        destroyCarousel(swiperNode)

        expect(swiperInstances[0].destroy).toHaveBeenCalledOnce()
        expect(
            intersectionObserverInstances[0].disconnect,
        ).toHaveBeenCalledOnce()
        expect(swiperNode.dataset.initialized).toBeUndefined()
        expect(swiperNode.swiper).toBeUndefined()
    })

    it('supports multi-row carousel settings without loop mode', () => {
        document.body.innerHTML = createCarouselMarkup(
            'data-carousel-id="rows-carousel" data-carousel-loop="1" data-carousel-rows="2"',
        )

        const swiperNode = document.querySelector('.swiper')
        const options = parseCarouselOptions(swiperNode)
        const controls = resolveCarouselControls(swiperNode, options)
        const abortController = new AbortController()

        const settings = buildSwiperSettings(
            swiperNode,
            options,
            controls,
            abortController.signal,
        )

        expect(options.rows).toBe(2)
        expect(settings.loop).toBe(false)
        expect(settings.grid).toEqual({
            fill: 'row',
            rows: 2,
        })
    })

    it('restarts autoplay when an enabled autoplay carousel stops unexpectedly', () => {
        vi.useFakeTimers()

        document.body.innerHTML = createCarouselMarkup(
            [
                'data-carousel-id="autoplay-carousel"',
                'data-carousel-autoplay="1"',
                'data-carousel-pagination="1"',
            ].join(' '),
        )

        const swiperNode = document.querySelector('.swiper')
        const options = parseCarouselOptions(swiperNode)
        const controls = resolveCarouselControls(swiperNode, options)
        const abortController = new AbortController()

        const settings = buildSwiperSettings(
            swiperNode,
            options,
            controls,
            abortController.signal,
        )
        const swiperInstance = {
            activeIndex: 0,
            autoplay: {
                start: vi.fn(),
            },
            destroyed: false,
            realIndex: 0,
        }

        settings.on.autoplayStop.call(swiperInstance)
        vi.runOnlyPendingTimers()

        expect(swiperInstance.autoplay.start).toHaveBeenCalledOnce()

        abortController.abort()
        vi.useRealTimers()
    })

    it('uses localized A11y labels, keyboard navigation, and a pause control', () => {
        document.documentElement.dataset.carouselLabelGoToSlide =
            'Aller "à" la diapositive :number & confirmer'
        document.documentElement.dataset.carouselLabelPause =
            'Mettre le carrousel en pause'
        document.documentElement.dataset.carouselLabelPlay = 'Lire le carrousel'
        document.body.innerHTML = createCarouselMarkup(
            [
                'data-carousel-id="accessible-carousel"',
                'data-carousel-autoplay="1"',
                'data-carousel-pagination="1"',
            ].join(' '),
        )

        const swiperNode = document.querySelector('.swiper')
        const swiperInstance = initCarousel(swiperNode)
        const toggle = document.querySelector('[data-carousel-autoplay-toggle]')

        expect(swiperInstance.settings.modules).not.toHaveLength(0)
        expect(swiperInstance.settings.keyboard).toEqual({
            enabled: true,
            onlyInViewport: true,
            pageUpDown: false,
        })
        expect(swiperInstance.settings.a11y).toMatchObject({
            containerMessage: 'Carousel',
            nextSlideMessage: 'Next slide',
            prevSlideMessage: 'Previous slide',
            slideLabelMessage: 'Slide {{index}} of {{slidesLength}}',
        })
        expect(
            swiperInstance.settings.pagination.renderBullet(1, 'bullet'),
        ).toContain(
            'aria-label="Aller &quot;à&quot; la diapositive 2 &amp; confirmer"',
        )
        expect(toggle).not.toBeNull()
        expect(toggle.getAttribute('aria-label')).toBe(
            'Mettre le carrousel en pause',
        )

        toggle.click()

        expect(swiperNode.dataset.carouselAutoplayPaused).toBe('true')
        expect(swiperInstance.autoplay.stop).toHaveBeenCalled()
        expect(toggle.getAttribute('aria-label')).toBe('Lire le carrousel')
    })

    it('keeps a user-paused carousel paused when Livewire re-initializes it', () => {
        document.body.innerHTML = createCarouselMarkup(
            [
                'data-carousel-id="persistent-pause-carousel"',
                'data-carousel-autoplay="1"',
            ].join(' '),
        )

        const swiperNode = document.querySelector('.swiper')
        initCarousel(swiperNode)

        document.querySelector('[data-carousel-autoplay-toggle]').click()
        destroyCarousel(swiperNode)
        const reinitializedSwiper = initCarousel(swiperNode)
        const toggle = document.querySelector('[data-carousel-autoplay-toggle]')

        expect(swiperNode.dataset.carouselAutoplayPaused).toBe('true')
        expect(toggle.getAttribute('aria-label')).toBe('Play carousel')
        expect(reinitializedSwiper.autoplay.stop).toHaveBeenCalled()
    })
})
