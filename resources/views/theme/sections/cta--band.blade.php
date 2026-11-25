@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;
@endphp
<section
    class="theme-cta theme-cta--band border-b border-[var(--foundation-border)] bg-[var(--foundation-inverse-bg)]"
>
    <div
        class="mx-auto flex max-w-7xl flex-col items-center gap-5 px-4 py-14 text-center sm:px-6 lg:py-20"
    >
        <h2
            class="max-w-3xl text-3xl leading-tight font-(family-name:--theme-heading-font) font-semibold text-white sm:text-4xl"
        >
            {{ $section->heading }}
        </h2>

        @if ($section->summary)
            <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">{{ $section->summary }}</p>
        @endif

        <div class="flex flex-wrap justify-center gap-3">
            @foreach ($section->actions as $action)
                @php
                    $safeActionUrl = PublicUrlSanitizer::sanitize($action['url'] ?? null);
                @endphp

                @continue($safeActionUrl === null)

                <a
                    href="{{ $safeActionUrl }}"
                    class="rounded-full bg-white px-5 py-3 text-sm font-semibold text-[var(--foundation-inverse-bg)] transition hover:bg-[var(--theme-accent)]"
                >
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
