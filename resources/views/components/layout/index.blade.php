@props ([
    'containerClass' => null,
    'footer' => null,
    'header' => null,
    'mainClass' => null,
    'mainContainerClass' => null,
    'pageSlot' => null,
])
@php
    use Capell\Frontend\Facades\Frontend;
    use Livewire\Blaze\Blaze;

    $theme ??= Frontend::theme();
    $page ??= Frontend::page();
    $layout ??= Frontend::layout();
    $site ??= Frontend::site();
    $layoutMeta = is_array($layout?->meta ?? null) ? $layout->meta : [];
    $themeMeta = is_array($theme?->meta ?? null) ? $theme->meta : [];
    $header ??= array_key_exists('header', $layoutMeta) ? $layoutMeta['header'] : null;
    $footer ??= array_key_exists('footer', $layoutMeta) ? $layoutMeta['footer'] : null;
    $layoutNeighborLinks ??= null;
@endphp

<div
    {{ $attributes->merge(['class' => 'flex min-h-screen flex-col bg-white dark:bg-gray-900']) }}
>
    <a
        class="capell-public-skip-link sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:text-gray-900 focus:shadow focus:outline-3 focus:outline-offset-2 focus:outline-blue-700"
        href="#main"
    >
        {{ __('capell-frontend::generic.skip_link') }}
    </a>

    @if ($header)
        {{ $header }}
    @elseif ($header === null && (! array_key_exists('header', $themeMeta) || $themeMeta['header'] !== false))
        @if (! empty($themeMeta['header_file']))
            <x-dynamic-component :component="$themeMeta['header_file']" />
        @else
            <x-capell::header.index />
        @endif
    @endif

    {{--
            Blaze's static component-tag optimization compiles the sibling
            tags in this view (header, content, logo) into inlined function
            calls sharing Blaze's own output-buffering bookkeeping. When
            `<x-capell::layout.main>` — a class-backed component that renders
            layout-builder's dynamic, render-hook-driven main content — is
            invoked immediately after those Blaze-compiled calls in the same
            buffer scope, Laravel's native component stack (`$__env`) desyncs
            and `capell::layout.main` silently renders empty output instead
            of the page's real widgets. Disabling Blaze around this one
            invocation mirrors the same defence
            `capell-layout-builder::components.layout.widget` and
            `Capell\LayoutBuilder\Support\RenderHooks\RegisterMainContentLayoutHook`
            already use around their own dynamic, hook-driven renders.
        --}}
    @php
            $wasBlazeEnabledForMain = Blaze::isEnabled();
            Blaze::disable();
        @endphp
    <x-capell::layout.main
        :$layout
        :$page
        :theme="$themeMeta"
        :layout-neighbor-links="$layoutNeighborLinks"
        :page-slot="$pageSlot ?? $slot"
        :container-class="$containerClass"
        :main-class="$mainClass"
        :main-container-class="$mainContainerClass"
    />
    @php
            if ($wasBlazeEnabledForMain) {
                Blaze::enable();
            }
        @endphp

    {{--
            Same Blaze static-tag desync as `<x-capell::layout.main>` above:
            re-enabling Blaze immediately before the footer leaves it
            rendering in the same corrupted `$__env` buffer scope
            `<x-capell::layout.main>`'s own comment describes, so the footer
            (including its plain, literal `<x-capell::footer.index />` tag
            path -- this isn't specific to `<x-dynamic-component>`) silently
            renders empty. Disabling Blaze around the footer too, the same
            way it's disabled around `layout.main`, fixes it.
        --}}
    @php
            $wasBlazeEnabledForFooter = Blaze::isEnabled();
            Blaze::disable();
        @endphp
    @if ($footer)
        {{ $footer }}
    @elseif ($footer !== false && (! array_key_exists('footer', $themeMeta) || $themeMeta['footer'] !== false))
        @if (($themeMeta['footer_file'] ?? 'capell::footer') === 'capell::footer')
            <x-capell::footer.index />
        @else
            <x-dynamic-component :component="$themeMeta['footer_file']" />
        @endif
    @endif
    @php
            if ($wasBlazeEnabledForFooter) {
                Blaze::enable();
            }
        @endphp
</div>
