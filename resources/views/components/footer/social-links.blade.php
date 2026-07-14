@props ([
    'links',
    'size' => 'md',
])
<div
    {{
        $attributes->class([
            'footer-social-links inline-flex flex-wrap items-center gap-2',
        ])
    }}
>
    @foreach ($links as $link)
        @php
            $icon = null;
            $label = $link['title'] ?? $link['type'] ?? parse_url((string) ($link['url'] ?? ''), PHP_URL_HOST) ?? __('capell-theme-foundation::generic.social_link');
            $iconClass = 'shrink-0 grow-0 opacity-50 group-hover/item:opacity-100' . match ($size) {
                'sm' => ' h-5 w-5',
                'md' => ' h-8 w-8',
                'lg' => ' h-10 w-10',
            };

            if (! empty($link['icon'])) {
                $icon = rescue(
                    fn (): mixed => svg($link['icon'], [
                        'class' => $iconClass,
                        'title' => $link['title'] ?? $link['type'],
                    ]),
                    rescue: null,
                    report: false,
                );
            }
        @endphp

        <a
            class="hover:text-primary focus:text-primary group/item flex items-center gap-x-1"
            href="{{ $link['url'] }}"
            aria-label="{{ $label }}"
            target="_blank"
            rel="nofollow noopener"
        >
            @if ($icon)
                {!! $icon !!}
            @elseif (! empty($link['file']))
                @php
                    $file = $link['file'];
                    $image = is_array($file) ? ($file[array_key_first($file)] ?? null) : $file;
                @endphp

                @if ($image)
                    <img
                        @class ([
                            'shrink-0 grow-0 overflow-hidden text-center leading-none brightness-0 contrast-[.5] invert sepia-0 filter group-hover/item:contrast-150 group-focus/item:contrast-150',
                            'h-6 w-6' => $size === 'xs',
                            'h-8 w-8' => $size === 'sm',
                            'h-10 w-10' => $size === 'md',
                            'h-12 w-12' => $size === 'lg',
                        ])
                        src="{{ asset('storage/' . $image) }}"
                        alt="{{ $link['title'] ?: $link['type'] }}"
                        loading="lazy"
                    />
                @endif
            @endif

            @if (! empty($link['title']))
                <span
                    class="inline-block text-start text-sm leading-none font-medium text-balance"
                >
                    {{ $link['title'] ?? str($link['type'])->title() }}
                </span>
            @endif
        </a>
    @endforeach
</div>
