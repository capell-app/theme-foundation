/**
 * consent-embed.js
 *
 * Loads a third-party embed only after the visitor presses its consent
 * button. The server renders the iframe inside an inert `<template>`, so no
 * request reaches the embed's origin until this module clones it in.
 *
 * Markup contract (rendered by the Foundation consent-embed component):
 * - Container `[data-capell-consent-embed]`.
 * - `[data-capell-consent-embed-placeholder]` holding the notice and a
 *   native `button[data-capell-consent-embed-load]`, so Enter and Space work.
 * - `template[data-capell-consent-embed-template]` holding the iframe.
 */
const loadEmbed = (container) => {
    const template = container.querySelector(
        'template[data-capell-consent-embed-template]',
    )
    const placeholder = container.querySelector(
        '[data-capell-consent-embed-placeholder]',
    )

    if (!template || !placeholder) {
        return
    }

    const frame = template.content.firstElementChild?.cloneNode(true)

    if (!frame) {
        return
    }

    placeholder.replaceWith(frame)
    template.remove()
    container.dataset.capellConsentEmbedLoaded = 'true'
    frame.setAttribute('tabindex', '-1')
    frame.focus()
}

document.addEventListener('click', (event) => {
    const button =
        event.target instanceof Element
            ? event.target.closest('[data-capell-consent-embed-load]')
            : null
    const container = button?.closest('[data-capell-consent-embed]')

    if (container) {
        loadEmbed(container)
    }
})
