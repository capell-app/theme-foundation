@props([
    'language',
    'element' => 'div',
    'pageLanguages' => [],
])
<{{ $element }}
    x-data="{
        open: false,
        toggle() {
            if (this.open) {
                return this.close()
            }

            this.$refs.button.focus()

            this.open = true

            document.getElementById('main-menu').scrollTo(0, 0)
        },
        close(focusAfter) {
            if (! this.open) return

            this.open = false

            focusAfter && focusAfter.focus()
        },
    }"
    x-on:keydown.escape.prevent.stop="close($refs.button)"
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
    x-id="['dropdown-button']"
    {{ $attributes->merge(['class' => 'group lg:relative']) }}
>
    {{ $trigger }}

    <ul
        x-ref="panel"
        x-on:click.outside="
            window.matchMedia('(min-width: {{ config('capell.breakpoints.lg') }}px)')
                .matches
                ? close($refs.button)
                : null
        "
        :id="$id('dropdown-button')"
        x-cloak
        class="capell-menu-languages-dropdown lg:right:-0 absolute inset-0 z-10 origin-top-left bg-white transition-[translate,visibility] duration-300 ease-in-out outline-none lg:top-full lg:bottom-auto lg:left-auto lg:-mt-2 lg:min-w-48 lg:translate-none lg:rounded-lg lg:border lg:border-gray-200 lg:p-1.5 lg:shadow-sm lg:transition-none dark:bg-gray-900 dark:lg:border-gray-700"
        x-bind:class="open ? 'visible opacity-100' : 'pointer-events-none invisible opacity-0'"
    >
        <li
            class="mb-2 border-b border-gray-200 lg:hidden dark:border-gray-700"
        >
            <button
                type="button"
                class="flex w-full cursor-pointer items-center bg-gray-100 px-6 py-2 text-left text-gray-800 transition-colors focus-visible:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 lg:px-3 lg:py-1.5 dark:bg-gray-800 dark:text-gray-200 dark:focus-visible:bg-gray-800"
                x-on:click="close($refs.button)"
            >
                @svg('heroicon-o-arrow-left', 'mr-1 h-5 w-5 stroke-current')
                <span>
                    {{ $language->name }}
                </span>
            </button>
        </li>
        @foreach ($pageLanguages as $pageLanguage)
            @continue($pageLanguage['id'] === $language->id)
            <li>
                <a
                    href="{{ $pageLanguage['url'] }}"
                    @wireNavigate
                    class="focus:text-primary hover:text-primary font-heading group flex w-full cursor-pointer items-center gap-x-2 px-6 py-3 text-sm font-semibold focus-visible:bg-gray-50 lg:gap-x-1 lg:!bg-transparent lg:px-4 lg:py-1.5 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800"
                >
                    <img
                        class="mr-2 inline-block h-4 w-4 align-top"
                        src="{{ asset("vendor/blade-country-flags/4x3-{$pageLanguage['flag']}.svg") }}"
                        alt=""
                        width="16"
                        height="16"
                        loading="lazy"
                    />
                    {{ $pageLanguage['name'] }}
                </a>
            </li>
        @endforeach
    </ul>
</{{ $element }}>
