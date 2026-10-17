import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import vm from 'node:vm'

class Element extends EventTarget {
    constructor(attributes = {}) {
        super()
        this.attributes = attributes
        this.children = []
        this.hidden = false
        this.parentElement = null
    }
    getAttribute(key) {
        return this.attributes[key] ?? null
    }
    hasAttribute(key) {
        return key in this.attributes
    }
    removeAttribute(key) {
        delete this.attributes[key]
    }
    closest(selector) {
        return selector === '.lightbox' && this.attributes['data-lightbox']
            ? this
            : null
    }
    contains(node) {
        return this === node || this.children.includes(node)
    }
    querySelectorAll() {
        return this.children
    }
    focus() {
        document.activeElement = this
    }
}
const document = new EventTarget()
const window = new EventTarget()
window.getComputedStyle = () => ({ display: 'block', visibility: 'visible' })
const first = new Element({
    'data-lightbox': '/first.jpg',
    'data-title': 'First image',
})
const second = new Element({
    'data-lightbox': '/second.jpg',
    alt: 'Second image',
})
const dialog = new Element()
const close = new Element()
const next = new Element()
close.parentElement = next.parentElement = dialog
dialog.children = [close, next]
const carousel = new EventTarget()
let carouselEnabled = true
carousel.addEventListener('disable-carousel', () => {
    carouselEnabled = false
})
carousel.addEventListener('enable-carousel', () => {
    carouselEnabled = true
})
// Viewers stay ignored and pending until their provider is registered.
const lateRoot = new Element({ 'x-ignore': '', 'data-lightbox-pending': '' })
document.querySelectorAll = (selector) =>
    selector === '.lightbox'
        ? [first, second]
        : selector === '[x-data="lightbox"]'
          ? [lateRoot]
          : []
document.getElementsByClassName = () => [carousel]
class CustomEvent extends Event {
    constructor(name, options) {
        super(name)
        this.detail = options.detail
    }
}
let factory
let viewer
vm.runInNewContext(
    readFileSync(
        new URL('../../resources/js/utilities/lightbox.js', import.meta.url),
        'utf8',
    ),
    {
        document,
        window,
        HTMLElement: Element,
        Event,
        CustomEvent,
        getComputedStyle: () => ({ display: 'block', visibility: 'visible' }),
    },
)
// Exercise the late asset lifecycle through Alpine's public registration boundary.
window.Alpine = {
    data: (name, callback) => {
        assert.equal(name, 'lightbox')
        factory = callback
    },
    mutateDom: (callback) => callback(),
    destroyTree: () => {
        dialog.hidden = true
    },
    initTree: () => {
        viewer = factory()
        viewer.$refs = { lightboxDialog: dialog }
        viewer.$nextTick = (callback) => callback()
        window.addEventListener('lightbox', (event) => viewer.lightbox(event))
    },
}
document.dispatchEvent(new Event('alpine:init'))
assert.equal(lateRoot.hasAttribute('x-ignore'), false)
assert.equal(lateRoot.hasAttribute('data-lightbox-pending'), false)
for (const key of ['Enter', ' ']) {
    first.focus()
    const event = new Event('keydown', { cancelable: true })
    Object.defineProperties(event, {
        key: { value: key },
        target: { value: first },
    })
    document.dispatchEvent(event)
    assert.equal(event.defaultPrevented, true)
    assert.equal(viewer.currentUrl, '/first.jpg')
    assert.equal(viewer.currentTitle, 'First image')
    assert.equal(document.activeElement, dialog)
    assert.equal(carouselEnabled, false)
    viewer.loadNext()
    assert.equal(viewer.currentUrl, '/second.jpg')
    assert.equal(viewer.currentTitle, 'Second image')
    viewer.loadNext()
    assert.equal(viewer.currentUrl, '/first.jpg')
    next.focus()
    const tab = new Event('keydown', { cancelable: true })
    Object.defineProperty(tab, 'key', { value: 'Tab' })
    viewer.trapFocus(tab)
    assert.equal(document.activeElement, close)
    assert.equal(tab.defaultPrevented, true)
    viewer.close()
    assert.equal(viewer.currentUrl, '')
    assert.equal(document.activeElement, first)
    assert.equal(carouselEnabled, true)
}
document.dispatchEvent(new Event('livewire:init'))
first.focus()
const click = new Event('click', { cancelable: true })
Object.defineProperty(click, 'target', { value: first })
document.dispatchEvent(click)
assert.equal(viewer.currentUrl, '/first.jpg')
console.log(
    'PASS keyboard activation, gallery wrap, focus trap, focus restoration, carousel coordination and late startup',
)
