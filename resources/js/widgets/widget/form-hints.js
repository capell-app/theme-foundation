/**
 * form-hints.js — v1.0.0
 *
 * Wave 4c §D "helpful-form-hints". Progressive validation encouragement:
 * as the visitor types into a hint-wired field, this module announces a
 * short positive-reinforcement message via an `aria-live="polite"` region
 * once the field looks reasonably filled in — not just error states. Pure
 * client-side text feedback; never submits or validates against a backend.
 *
 * Public data-attribute contract:
 * - `[data-form-encouraging]` (section root)
 * - `[data-form-hint-field]` with `data-form-hint-encouragement`: the
 *   configured encouragement copy for that field
 * - `[data-form-hint-message][data-form-hint-message-for="<fieldId>"]`:
 *   the `aria-live="polite"` region that receives the message
 */

function fieldLooksFilled(field) {
    const value = field.value.trim()

    if (field.type === 'email') {
        return value.includes('@') && value.includes('.')
    }

    return value.length >= 3
}

function findMessageNode(root, fieldId) {
    return root.querySelector(
        `[data-form-hint-message][data-form-hint-message-for="${fieldId}"]`,
    )
}

function updateHint(root, field) {
    const messageNode = findMessageNode(root, field.id)

    if (!messageNode) {
        return
    }

    if (!fieldLooksFilled(field)) {
        messageNode.textContent = ''

        return
    }

    const encouragement = field.dataset.formHintEncouragement

    messageNode.textContent =
        encouragement && encouragement !== '' ? encouragement : 'Looks good.'
}

function initFormHints(root) {
    if (root.dataset.formHintsInitialized === 'true') {
        return
    }

    root.querySelectorAll('[data-form-hint-field]').forEach((field) => {
        field.addEventListener('input', () => updateHint(root, field))
    })

    root.dataset.formHintsInitialized = 'true'
}

export function initAllFormHints(root = document) {
    root.querySelectorAll('[data-form-encouraging]').forEach((section) => {
        initFormHints(section)
    })
}

if (typeof document !== 'undefined') {
    initAllFormHints()

    document.addEventListener('livewire:navigated', () => {
        initAllFormHints()
    })
}
