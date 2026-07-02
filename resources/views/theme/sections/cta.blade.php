<section class="theme-cta border-b border-slate-200/80 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:py-16">
        <div
            class="rounded-[var(--theme-radius-value)] bg-slate-950 p-7 text-white sm:p-10 lg:grid lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-8"
        >
            <h2
                class="text-3xl leading-tight font-[var(--theme-heading-font)] font-semibold sm:text-4xl"
            >
                {{ $section->heading }}
            </h2>
            <div class="mt-5 lg:mt-0">
                @if ($section->summary)
                    <p class="text-sm leading-7 text-slate-300 sm:text-base">
                        {{ $section->summary }}
                    </p>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
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
        </div>
    </div>
</section>
