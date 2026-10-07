{{-- @blaze-standard-compiler: class-backed footer views need the native component data. --}}
@props([
    'pages',
    'linkedPages' => collect(),
])

@if ($pages?->isNotEmpty() === true)
    <div {{ $attributes->class(['footer-latest-pages xl:w-[22%]']) }}>
        <div class="{{ $headingClass }} mb-4">
            {{ __('capell-theme-foundation::generic.latest_pages') }}
        </div>

        <ul class="space-y-2">
            @foreach ($linkedPages as $linkedPage)
                <li>
                    <a
                        href="{{ $linkedPage->url }}"
                        class="focus:text-primary hover:text-primary widget text-sm leading-tight font-medium text-[var(--color-footer-link)]"
                        wire:navigate
                    >
                        {{ $linkedPage->label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
