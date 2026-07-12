@props ([
    'brand',
    'themePrefix',
    'chromeHeader' => null,
    'chromeFooter' => null,
    'mainContent' => null,
    'content' => '',
])

<a
    href="#main-content"
    class="{{ $themePrefix }}-skip-link"
>
    {{ __('capell-frontend::generic.skip_to_content') }}
</a>

<div
    style="{{ collect($brand->tokens())->map(fn (mixed $value, string $token): string => $token . ':' . $value)->implode(';') }}"
    class="{{ $themePrefix }}-shell min-h-screen antialiased"
>
    @if ($chromeHeader !== null || $chromeFooter !== null)
        {!! $chromeHeader ?? '' !!}
        <main id="main-content">{!! $mainContent ?? $content !!}</main>
        {!! $chromeFooter ?? '' !!}
    @else
        <main id="main-content">{!! $content !!}</main>
    @endif
</div>
