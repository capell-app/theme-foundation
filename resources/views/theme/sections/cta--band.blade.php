<section
    class="theme-cta theme-cta--band border-b border-slate-200/80 bg-slate-950"
>
    <div
        class="mx-auto flex max-w-7xl flex-col items-center gap-5 px-4 py-14 text-center sm:px-6 lg:py-20"
    >
        <h2
            class="max-w-3xl text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold text-white sm:text-4xl"
        >
            {{ $section->heading }}
        </h2>

        @if ($section->summary)
            <p class="max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                {{ $section->summary }}
            </p>
        @endif

        <div class="flex flex-wrap justify-center gap-3">
            @foreach ($section->actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-[var(--theme-accent)]"
                >
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
