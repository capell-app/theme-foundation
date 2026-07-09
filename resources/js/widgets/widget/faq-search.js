/**
 * faq-search.js — v1.0.0
 *
 * Wave 4c §D. Type-to-filter over the `faq-search-discovery` section's FAQ
 * list. Plain substring matching only (no fuzzy-search library, per the
 * programme's §0.4 guardrail) — matches against each item's question and
 * answer text, hides non-matching `<details>` items, and wraps the matched
 * substring in `<mark>` for a visual highlight. Also drives the optional
 * category-tab filter in the "categorised" variant, composing with the
 * text filter (an item must satisfy both).
 *
 * Public data-attribute contract:
 * - `[data-faq-search-discovery]` (section root)
 * - `[data-faq-search-input]`: the `<input type="search">`
 * - `[data-faq-search-status]`: `aria-live="polite"` result-count region
 * - `[data-faq-search-item]` with `data-faq-search-question` /
 *   `data-faq-search-answer` (plain-text copies for matching) and an
 *   optional `data-category`
 * - `[data-faq-search-question-text]` / `[data-faq-search-answer-text]`:
 *   nodes whose text gets the `<mark>` highlight treatment
 * - `[data-faq-search-category-tab]` with `data-category` (categorised
 *   variant only)
 * - `[data-faq-search-empty]`: shown when nothing matches
 */

function escapeHtml(value) {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
}

function highlight(node, originalText, query) {
    if (query === '') {
        node.textContent = originalText

        return
    }

    const lowerText = originalText.toLowerCase()
    const lowerQuery = query.toLowerCase()
    const matchIndex = lowerText.indexOf(lowerQuery)

    if (matchIndex === -1) {
        node.textContent = originalText

        return
    }

    const before = escapeHtml(originalText.slice(0, matchIndex))
    const match = escapeHtml(
        originalText.slice(matchIndex, matchIndex + query.length),
    )
    const after = escapeHtml(originalText.slice(matchIndex + query.length))

    node.innerHTML = `${before}<mark>${match}</mark>${after}`
}

function itemMatchesQuery(item, query) {
    if (query === '') {
        return true
    }

    const question = (item.dataset.faqSearchQuestion ?? '').toLowerCase()
    const answer = (item.dataset.faqSearchAnswer ?? '').toLowerCase()

    return question.includes(query) || answer.includes(query)
}

function itemMatchesCategory(item, category) {
    if (!category || category === 'all') {
        return true
    }

    return item.dataset.category === category
}

function applyFilters(root) {
    const input = root.querySelector('[data-faq-search-input]')
    const query = (input?.value ?? '').trim().toLowerCase()
    const activeCategoryTab = root.querySelector(
        '[data-faq-search-category-tab][data-active="true"]',
    )
    const category = activeCategoryTab?.dataset.category ?? 'all'
    const items = Array.from(root.querySelectorAll('[data-faq-search-item]'))
    let visibleCount = 0

    items.forEach((item) => {
        const matches =
            itemMatchesQuery(item, query) && itemMatchesCategory(item, category)

        item.hidden = !matches

        if (matches) {
            visibleCount += 1
        }

        const questionNode = item.querySelector(
            '[data-faq-search-question-text]',
        )
        const answerNode = item.querySelector('[data-faq-search-answer-text]')

        if (questionNode) {
            highlight(questionNode, item.dataset.faqSearchQuestion ?? '', query)
        }

        if (answerNode) {
            highlight(answerNode, item.dataset.faqSearchAnswer ?? '', query)
        }
    })

    const status = root.querySelector('[data-faq-search-status]')

    if (status) {
        status.textContent =
            query === ''
                ? ''
                : `${visibleCount} ${visibleCount === 1 ? 'result' : 'results'} for "${input.value.trim()}"`
    }

    const emptyState = root.querySelector('[data-faq-search-empty]')

    if (emptyState) {
        emptyState.classList.toggle(
            'hidden',
            visibleCount !== 0 || items.length === 0,
        )
    }
}

function setActiveCategoryTab(root, tab) {
    root.querySelectorAll('[data-faq-search-category-tab]').forEach(
        (candidate) => {
            candidate.dataset.active = candidate === tab ? 'true' : 'false'
        },
    )
}

function initFaqSearch(root) {
    if (root.dataset.faqSearchInitialized === 'true') {
        return
    }

    const input = root.querySelector('[data-faq-search-input]')

    input?.addEventListener('input', () => applyFilters(root))

    root.querySelectorAll('[data-faq-search-category-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            setActiveCategoryTab(root, tab)
            applyFilters(root)
        })
    })

    const firstTab = root.querySelector('[data-faq-search-category-tab]')

    if (firstTab) {
        setActiveCategoryTab(root, firstTab)
    }

    root.dataset.faqSearchInitialized = 'true'
}

export function initAllFaqSearches(root = document) {
    root.querySelectorAll('[data-faq-search-discovery]').forEach((section) => {
        initFaqSearch(section)
    })
}

if (typeof document !== 'undefined') {
    initAllFaqSearches()

    document.addEventListener('livewire:navigated', () => {
        initAllFaqSearches()
    })
}
