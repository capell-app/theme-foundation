import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import vm from 'node:vm'

const root = resolve(import.meta.dirname, '../..')
const manifest = JSON.parse(
    readFileSync(resolve(root, 'publishes/build/manifest.json'), 'utf8'),
)
const published = resolve(
    root,
    'publishes/build',
    manifest['resources/js/widgets/widget/carousel.js'].file,
)

for (const path of [
    resolve(root, 'resources/js/widgets/widget/carousel.js'),
    published,
]) {
    let reducedMotion = false
    const context = vm.createContext({
        console,
        window: { matchMedia: () => ({ matches: reducedMotion }) },
    })
    const modules = new Map()
    const load = (path) => {
        if (!modules.has(path)) {
            modules.set(
                path,
                new vm.SourceTextModule(readFileSync(path, 'utf8'), {
                    context,
                    identifier: path,
                }),
            )
        }
        return modules.get(path)
    }
    const module = load(path)
    await module.link((specifier, parent) => {
        if (specifier === 'swiper' || specifier === 'swiper/modules') {
            const names =
                specifier === 'swiper'
                    ? ['Swiper']
                    : [
                          'Navigation',
                          'Pagination',
                          'Autoplay',
                          'A11y',
                          'Keyboard',
                          'EffectFade',
                          'Mousewheel',
                          'Grid',
                      ]
            return new vm.SyntheticModule(
                names,
                function () {
                    for (const name of names) this.setExport(name, class {})
                },
                { context },
            )
        }
        return load(resolve(dirname(parent.identifier), specifier))
    })
    await module.evaluate()
    const labels = {
        'data-carousel-label-go-to-slide': 'Diapositiva :number',
        'data-carousel-label-pause': 'Pausar',
        'data-carousel-label-play': 'Reproducir',
    }
    const attributes = {
        'data-carousel-autoplay': '1',
        'data-carousel-id': 'customer-carousel',
    }
    const node = {
        getAttribute: (key) => attributes[key] ?? null,
        setAttribute: (key, value) => {
            attributes[key] = value
        },
        querySelectorAll: () => [{}, {}, {}],
        ownerDocument: {
            documentElement: { getAttribute: (key) => labels[key] ?? null },
        },
    }
    const options = module.namespace.parseCarouselOptions(node)
    assert.equal(options.labels.goToSlide, 'Diapositiva :number')
    assert.equal(options.labels.pause, 'Pausar')
    assert.equal(options.labels.play, 'Reproducir')
    assert.equal(options.autoplayEnabled, true)
    const settings = module.namespace.buildSwiperSettings(
        node,
        options,
        { controls: null, dotsNode: null, prevBtn: null, nextBtn: null },
        new AbortController().signal,
    )
    assert.equal(settings.a11y.enabled, true)
    assert.equal(settings.keyboard.enabled, true)
    reducedMotion = true
    assert.equal(
        module.namespace.parseCarouselOptions(node).autoplayEnabled,
        false,
    )
    console.log(`PASS translated controls and reduced motion: ${path}`)
}
