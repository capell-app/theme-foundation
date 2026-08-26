@props([
    'container' => '',
    'containerKey',
    'containerWidth' => null,
    'items' => collect(),
    'loop',
    'widget',
])
@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;
@endphp
@if ($items->isNotEmpty() || ! config('capell-layout-builder.widget.skip_render_empty', true))
    <x-capell-theme-foundation::widget.wrapper
        class="capell-navigation-tabs widget-navigation-tabs"
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :$widget
    >
        <ul
            class="tab-items mt-10 mb-4 flex flex-col flex-wrap items-center gap-4 border-b border-gray-100 px-2 text-center text-sm font-medium text-gray-500 md:flex-row"
        >
            @foreach ($items as $item)
                @php
                    $safeItemUrl = PublicUrlSanitizer::sanitize($item->data['url'] ?? null) ?? '#';
                @endphp
                <li class="tab-item -mb-px">
                    <a
                        href="{{ $safeItemUrl }}"
                        @class([
                            'hover:bg-primary inline-block rounded-t border-b-2 border-transparent px-4 py-3 hover:text-white',
                            'border-b-primary' => $item->active,
                        ])
                        @wireNavigate
                    >
                        {{ $item->label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </x-capell-theme-foundation::widget.wrapper>
@endif
