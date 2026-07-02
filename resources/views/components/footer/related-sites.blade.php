@props([
    'relatedSites' => collect(),
])

@if ($relatedSites->isNotEmpty())
    <div {{ $attributes->class(['space-y-4']) }}>
        <div
            class="grid items-center gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            @foreach ($relatedSites as $relatedSite)
                <a
                    class="flex items-center gap-x-2 gap-y-1 text-[var(--color-footer)] lg:grid"
                    href="{{ $relatedSite['url'] }}"
                    @wireNavigate
                >
                    <span
                        class="text-link text-lg font-bold"
                        style="{{ $relatedSite['primaryColor'] ? 'color:' . $relatedSite['primaryColor'] : '' }}"
                    >
                        {{ $relatedSite['title'] }}
                    </span>
                    @if ($relatedSite['description'])
                        <span class="text-sm leading-tight">
                            {{ $relatedSite['description'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif
