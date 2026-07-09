@props ([
    'items' => null,
    'headingClass' => '',
    'menuItemClass' => 'focus:text-primary hover:text-primary text-sm leading-tight font-medium break-all text-[var(--color-footer-link)] xl:text-base',
    'menuSubItemClass' => 'focus:text-primary hover:text-primary py-1 text-xs leading-tight font-medium text-[var(--color-footer-muted)] xl:text-sm',
])
@php
    use Illuminate\Support\Collection;

    /**
     * @var Collection<NavigationItemData> $items
     */
    $childLabels = $items
        ->flatMap(fn (mixed $item): Collection => $item->children->pluck('label'))
        ->unique()
        ->values();

    $items = $items
        ->reject(fn (mixed $item): bool => $childLabels->contains($item->label))
        ->unique(fn (mixed $item): string => $item->label . '|' . ($item->data['url'] ?? ''))
        ->values();

    $half = (int) ceil($items->count() / 2);

    /**
     * @var Collection<Collection<NavigationItemData>> $chunks
     */
    $chunks = $items->chunk($half);
@endphp

<nav
    {{ $attributes->merge(['id' => 'footer-menu', 'aria-label' => __('capell-theme-foundation::generic.footer_navigation')]) }}
>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($chunks as $chunk)
            <ul class="flex flex-col gap-y-4">
                @foreach ($chunk as $id => $item)
                    <li
                        @class ([
                            'nav-item',
                            'active' => $item->active,
                        ])
                    >
                        <a
                            href="{{ $item->data['url'] ?? '' }}"
                            @wireNavigate
                            class="{{ $menuItemClass }}"
                        >
                            {{ $item->label }}
                        </a>
                        @if ($item->children->count() > 0)
                            <ul class="mt-2 flex flex-col gap-y-1">
                                @foreach ($item->children as $child)
                                    <li
                                        class="nav-child-item before:content-['-']"
                                    >
                                        <a
                                            href="{{ $child->data['url'] ?? '' }}"
                                            @wireNavigate
                                            @class ([
                                                $menuSubItemClass,
                                                'active' => $child->active,
                                            ])
                                        >
                                            {{ $child->label }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</nav>
