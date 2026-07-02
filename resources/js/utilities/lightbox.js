/* global Alpine */
;(function () {
    const querySelector = '.lightbox'

    const lightboxAttr = 'data-lightbox'

    const defaultGroup = 'default'
    const groupAttr = 'data-group'
    const titleAttr = 'data-title'
    const typeAttr = 'data-type'
    const altAttr = 'alt'

    document.addEventListener('livewire:init', () => {
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
    })
})()
