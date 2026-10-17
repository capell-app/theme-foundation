{{-- @blaze-standard-compiler: class-backed layout views need the native component stack, including the empty-layout fallback. --}}
@props([
    'containerClass' => null,
    'footer' => null,
    'header' => null,
    'mainClass' => null,
    'mainContainerClass' => null,
    'pageSlot' => null,
])
@php
    use Capell\Frontend\Facades\Frontend;

    $theme ??= Frontend::theme();
    $page ??= Frontend::page();
    $layout ??= Frontend::layout();
    $site ??= Frontend::site();
    $layoutMeta = is_array($layout?->meta ?? null) ? $layout->meta : [];
    $themeMeta = is_array($theme?->meta ?? null) ? $theme->meta : [];
    $themeData = ['meta' => $themeMeta];
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

    {{-- Keep nested compilation in the caller's Blaze mode; class-backed
         layout views use the standard compiler registration above. --}}
    <x-capell::layout.main
        :$layout
        :$page
        :theme="$themeData"
        :layout-neighbor-links="$layoutNeighborLinks"
        :page-slot="$pageSlot ?? $slot"
        :container-class="$containerClass"
        :main-class="$mainClass"
        :main-container-class="$mainContainerClass"
    />
    @if ($footer)
        {{ $footer }}
    @elseif ($footer !== false && (! array_key_exists('footer', $themeMeta) || $themeMeta['footer'] !== false))
        @if (($themeMeta['footer_file'] ?? 'capell::footer') === 'capell::footer')
            <x-capell::footer.index />
        @else
            <x-dynamic-component :component="$themeMeta['footer_file']" />
        @endif
    @endif
</div>
