@props ([
    'authorName',
    'authorAvatar' => null,
    'date' => null,
    'readTime' => null,
])

{{--
    Shared Foundation display primitive (Wave 2.7): author name/avatar plus
    a metadata row (date, read-time, and any additional metadata items
    passed via the `metadata` slot). Purely presentational, payload-driven,
    token-skinned via `--foundation-*` properties.
--}}

<div
    {{ $attributes->merge(['class' => 'byline-with-metadata flex items-center gap-3']) }}
>
    @if ($authorAvatar)
        <img
            src="{{ $authorAvatar }}"
            alt="{{ $authorName }}"
            loading="lazy"
            class="h-10 w-10 rounded-full object-cover"
        />
    @endif

    <div class="flex flex-col">
        <span
            class="text-sm font-semibold"
            style="color: var(--foundation-body-fg)"
        >
            {{ $authorName }}
        </span>

        <div
            class="flex items-center gap-2 text-xs"
            style="color: var(--foundation-border-strong)"
        >
            @if ($date)
                <span>{{ $date }}</span>
            @endif

            @if ($date && $readTime)
                <span aria-hidden="true">&middot;</span>
            @endif

            @if ($readTime)
                <span>{{ $readTime }}</span>
            @endif

            @isset ($metadata)
                <span aria-hidden="true">&middot;</span>
                {{ $metadata }}
            @endisset
        </div>
    </div>
</div>
