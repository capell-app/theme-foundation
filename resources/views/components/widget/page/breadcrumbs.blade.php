@props ([
    'ancestors' => null,
    'container',
    'containerKey',
    'containerWidth' => null,
    'currentPageLabel' => '',
    'homeLabel' => null,
    'homeUrl' => null,
    'loop',
    'page' => null,
    'showCurrentPage' => false,
    'widget',
])

@if ($page && $ancestors)
    <nav
        class="capell-page-breadcrumbs breadcrumbs my-4 text-gray-800"
        aria-label="{{ __('capell-frontend::generic.breadcrumbs') }}"
    >
        <x-capell-theme-foundation::widget.wrapper
            :$container
            :$containerKey
            :$containerWidth
            :index="$loop->index"
            :margin="[]"
            :$widget
            container-class="flex"
        >
            <ol
                class="inline-flex flex-wrap items-center space-x-1 md:space-x-2"
            >
                @if ($homeUrl && $homeLabel)
                    <li class="inline-flex items-center">
                        <a
                            class="hover:text-primary focus:text-primary inline-flex items-center text-sm font-medium text-gray-400"
                            href="{{ $homeUrl }}"
                            @wireNavigate
                        >
                            @svg ('heroicon-m-home', 'h-4 w-4 fill-current')
                            <span class="sr-only"> {{ $homeLabel }} </span>
                        </a>
                    </li>
                @endif

                @foreach ($ancestors as $ancestor)
                    <li>
                        <div class="flex items-center">
                            @svg ('heroicon-m-chevron-right', 'mr-1 h-4 w-4 text-gray-400')
                            <a
                                class="hover:text-primary focus:text-primary text-gray line-clamp-1 text-sm font-medium dark:text-gray-400"
                                href="{{ $ancestor->pageUrl->full_url }}"
                                title="{{ htmlspecialchars(strip_tags($ancestor->translation->label)) }}"
                                @wireNavigate
                            >
                                {{ $ancestor->translation->label }}
                            </a>
                        </div>
                    </li>
                @endforeach

                @if ($showCurrentPage)
                    <li aria-current="page">
                        <div class="flex items-center">
                            @svg ('heroicon-m-chevron-right', 'mr-1 h-4 w-4 text-gray-400')
                            <span
                                class="text-sm font-light text-gray-500"
                                title="{{ htmlspecialchars(strip_tags($currentPageLabel)) }}"
                            >
                                {{ $currentPageLabel }}
                            </span>
                        </div>
                    </li>
                @endif
            </ol>
        </x-capell-theme-foundation::widget.wrapper>
    </nav>
@endif
