<section
    class="theme-hero theme-hero--full-bleed relative border-b border-slate-200/80 bg-slate-950"
>
    @if ($section->mediaUrl)
        <img
            src="{{ $section->mediaUrl }}"
            alt="{{ $section->mediaAlt ?? '' }}"
            width="1920"
            height="960"
            loading="eager"
            decoding="async"
            fetchpriority="high"
            sizes="100vw"
            class="absolute inset-0 h-full w-full object-cover opacity-70"
        />
    @endif

    <div
        class="relative mx-auto flex min-h-[28rem] max-w-7xl flex-col justify-end gap-5 px-4 py-12 sm:px-6 lg:py-20"
    >
        @if ($section->eyebrow)
            <p
                class="text-xs font-semibold tracking-[0.12em] text-white uppercase"
            >
                {{ $section->eyebrow }}
            </p>
        @endif

        <h1
            class="max-w-4xl text-4xl leading-tight font-[var(--theme-heading-font)] font-semibold text-white sm:text-5xl lg:text-6xl"
        >
            {{ $section->heading }}
        </h1>

        @if ($section->summary)
            <p class="max-w-2xl text-base leading-8 text-slate-200 sm:text-lg">
                {{ $section->summary }}
            </p>
        @endif

        @if ($section->actions !== [])
            <div class="flex flex-wrap gap-3">
                @foreach ($section->actions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-white/60 text-white hover:border-white' : 'bg-white text-slate-950 hover:opacity-90' }} rounded-full px-5 py-3 text-sm font-semibold transition"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
