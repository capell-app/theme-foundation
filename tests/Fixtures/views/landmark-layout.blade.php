<div
    class="stub-shell"
    style="{{ collect($brand->tokens())->map(fn (mixed $value, string $token): string => $token . ':' . $value)->implode(';') }}"
>
    {!! $chromeHeader !!}
    <main id="main-content">{!! $mainContent !!}</main>
    {!! $chromeFooter !!}
</div>
