/**
 * lightbox.js — v1.0.0
 *
 * Shared Foundation JS module suite (Wave 2.6). Delegates the lightbox
 * dialog itself to an Alpine component (`x-data="lightbox"`, registered
 * here as `Alpine.data('lightbox', ...)`); this file only wires up trigger
 * discovery, keyboard activation, and carousel pause/resume coordination.
 *
 * Public data-attribute contract (all on elements matching `.lightbox`):
 * - `data-lightbox` (required): the media URL to open.
 * - `data-group`: gallery group name; Previous/Next cycle within the same
 *   group. Defaults to `"default"`.
 * - `data-type`: media type passed through to the Alpine component (e.g.
 *   `"image"` or `"video"`); the Alpine template decides how to render it.
 * - `data-title`: caption text; falls back to the element's `alt` attribute.
 *
 * Activation: click or Enter/Space on any `.lightbox` element (event
 * delegation on `document`, so elements can be added/removed freely).
 * Opening a lightbox dispatches a `disable-carousel` event at every
 * `.swiper` node so background carousels stop autoplaying underneath the
 * dialog; closing dispatches `enable-carousel` to resume them. Focus is
 * restored to the triggering element on close.
 *
 * The Alpine component (`lightbox`) exposes `load(group, index)`,
 * `close()`, `loadPrevious()`, `loadNext()`, a `lightbox(event)` handler for
 * the `window` `"lightbox"` CustomEvent this file dispatches, and `total()`
 * for pager UI. Registration supports both normal Alpine startup and assets
 * loaded after Alpine has already scanned the document.
 */

;(function () {
    const querySelector = '.lightbox'

    const lightboxAttr = 'data-lightbox'

    const defaultGroup = 'default'
    const groupAttr = 'data-group'
    const titleAttr = 'data-title'
    const typeAttr = 'data-type'
    const altAttr = 'alt'
    let initialized = false

    function initialize() {
        const Alpine = window.Alpine

        if (initialized || !Alpine) {
            return
        }

        initialized = true
        let media = {}

        function collectMedia() {
            media = {}

            document.querySelectorAll(querySelector).forEach((element) => {
                const group = element.getAttribute(groupAttr) || defaultGroup

                if (!media[group]) {
                    media[group] = []
                }

                media[group].push({
                    type: element.getAttribute(typeAttr),
                    url: element.getAttribute(lightboxAttr),
                    title:
                        element.getAttribute(titleAttr) ||
                        element.getAttribute(altAttr),
                })
            })
        }

        function disableCarousels() {
            const carousels = document.getElementsByClassName('swiper')

            for (let index = 0; index < carousels.length; index++) {
                carousels[index].dispatchEvent(new Event('disable-carousel'))
            }
        }

        function openLightboxFromElement(element) {
            collectMedia()

            window.dispatchEvent(
                new CustomEvent('lightbox', {
                    detail: {
                        group: element.getAttribute(groupAttr) || defaultGroup,
                        type: element.getAttribute(typeAttr),
                        url: element.getAttribute(lightboxAttr),
                        title:
                            element.getAttribute(titleAttr) ||
                            element.getAttribute(altAttr),
                    },
                }),
            )

            disableCarousels()
        }

        document.addEventListener('click', (event) => {
            const element = event.target.closest(querySelector)

            if (!element) {
                return
            }

            event.preventDefault()
            openLightboxFromElement(element)
        })

        document.addEventListener('keydown', (event) => {
            if (!['Enter', ' '].includes(event.key)) {
                return
            }

            const element = event.target.closest(querySelector)

            if (!element) {
                return
            }

            event.preventDefault()
            openLightboxFromElement(element)
        })

        Alpine.data('lightbox', () => ({
            currentIndex: null,
            currentGroup: null,
            currentType: null,
            currentTitle: '',
            currentUrl: '',
            previousFocus: null,

            load(group, index) {
                if (!media[group] || !media[group][index]) {
                    return false
                }

                this.currentGroup = group
                this.currentIndex = index
                this.currentTitle = media[group][index].title || ''
                this.currentType = media[group][index].type || 'image'
                this.currentUrl = media[group][index].url || ''

                this.$nextTick(() => {
                    this.$refs.lightboxDialog?.focus()
                })
            },

            close: function () {
                this.currentGroup = null
                this.currentIndex = null
                this.currentTitle = ''
                this.currentType = null
                this.currentUrl = ''

                this.previousFocus?.focus?.()
                this.previousFocus = null

                let carousels = document.getElementsByClassName('swiper')
                for (let i = 0; i < carousels.length; i++) {
                    carousels[i].dispatchEvent(new Event('enable-carousel'))
                }
            },

            loadPrevious() {
                if (!media[this.currentGroup]) return false

                let index = this.currentIndex - 1
                if (index === -1) {
                    index = media[this.currentGroup].length - 1
                }

                this.load(this.currentGroup, index)
            },

            loadNext() {
                if (!media[this.currentGroup]) return false

                let index = this.currentIndex + 1
                if (index === media[this.currentGroup].length) {
                    index = 0
                }

                this.load(this.currentGroup, index)
            },

            lightbox(event) {
                collectMedia()

                if (!media[event.detail.group]) {
                    return false
                }

                let index = media[event.detail.group].findIndex(
                    (x) => x.url === event.detail.url,
                )

                if (index !== -1) {
                    this.previousFocus = document.activeElement
                    this.load(event.detail.group, index)
                }
            },

            total() {
                return media[this.currentGroup]
                    ? media[this.currentGroup].length
                    : 0
            },
        }))

        document.querySelectorAll('[x-data="lightbox"]').forEach((root) => {
            const componentState = root._x_dataStack?.[0]

            if (!componentState || Object.keys(componentState).length > 0) {
                return
            }

            Alpine.destroyTree(root)
            Alpine.initTree(root)
        })
    }

    document.addEventListener('alpine:init', initialize)
    document.addEventListener('livewire:init', initialize)
    initialize()
})()
