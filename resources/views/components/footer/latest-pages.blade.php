@php
    $hasPages = $pages?->isNotEmpty() === true;
@endphp

@if ($hasPages)
    <div {{ $attributes->class(['footer-latest-pages xl:w-[22%]']) }}>
        <div class="{{ $headingClass }} mb-4">
            {{ __('capell-theme-foundation::generic.latest_pages') }}
        </div>

        <ul class="space-y-2">
            @foreach ($pages as $page)
                <li>
                    <a
                        href="{{ $page->pageUrl->full_url }}"
                        class="focus:text-primary hover:text-primary widget text-sm leading-tight font-medium text-[var(--color-footer-link)]"
                        wire:navigate
                    >
                        {{ $page->getTranslation('label') ?? $page->getTranslation('title') ?? $page->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
