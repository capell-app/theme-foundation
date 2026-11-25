@php
    // The brand profile's tokens are all custom properties.
    $brandTokenStyle = collect($brand->tokens())
        ->map(fn (mixed $value, string $token): string => $token . ':' . $value)
        ->implode(';');
@endphp

<a
    href="#main-content"
    class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-[var(--foundation-card-bg)] focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-[var(--foundation-heading-fg)] focus:shadow-lg"
>
    {{ __('capell-frontend::generic.skip_to_content') }}
</a>

<div
    style="{{ $brandTokenStyle }}"
    class="site-theme-shell flex min-h-screen flex-col bg-[var(--theme-surface)] font-(family-name:--theme-body-font) text-[var(--theme-foreground)] antialiased"
>
    <div
        id="theme-status"
        class="sr-only"
        role="status"
        aria-live="polite"
        aria-atomic="true"
    ></div>

    @if (isset($chromeHeader) || isset($chromeFooter))
        {!! $chromeHeader ?? '' !!}
        <main id="main-content">{!! $mainContent ?? $content !!}</main>
        {!! $chromeFooter ?? '' !!}
    @else
        <main id="main-content">{!! $content !!}</main>
    @endif
</div>
