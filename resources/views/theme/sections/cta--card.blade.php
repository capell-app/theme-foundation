<section
    class="theme-cta theme-cta--card border-b border-slate-200/80 bg-[var(--theme-surface)]"
>
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:py-16">
        <div
            class="rounded-[var(--theme-radius-value)] border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-12"
        >
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-4xl"
            >
                {{ $section->heading }}
            </h2>

            @if ($section->summary)
                <p
                    class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base"
                >
                    {{ $section->summary }}
                </p>
            @endif

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                @foreach ($section->actions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-slate-300 text-slate-800 hover:border-slate-950' : 'bg-[var(--theme-primary)] text-white hover:opacity-90' }} rounded-full px-5 py-3 text-sm font-semibold transition"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
