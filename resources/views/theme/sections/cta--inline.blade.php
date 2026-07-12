<section
    class="theme-cta theme-cta--inline border-b border-slate-200/80 bg-white"
>
    <div
        class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:py-10"
    >
        <div class="min-w-0">
            <h2
                class="text-xl leading-tight font-[var(--theme-heading-font)] font-semibold text-slate-950 sm:text-2xl"
            >
                {{ $section->heading }}
            </h2>

            @if ($section->summary)
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        <div class="flex shrink-0 flex-wrap gap-3">
            @foreach ($section->actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-slate-300 text-slate-800 hover:border-slate-950' : 'bg-[var(--theme-primary)] text-white hover:opacity-90' }} rounded-full px-5 py-2.5 text-sm font-semibold transition"
                >
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
