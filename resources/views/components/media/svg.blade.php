{{-- @blaze-standard-compiler: the class-backed SVG needs its sanitised component data. --}}
<svg
    width="{{ $width }}"
    height="{{ $height }}"
    xmlns="http://www.w3.org/2000/svg"
    @if ($viewBox) viewBox="{{ $viewBox }}" @endif
    {!! $attributes->except(['width', 'height', 'viewBox']) !!}
>
    {!! $contents !!}
</svg>
