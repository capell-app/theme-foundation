@php
    use Capell\Frontend\Facades\Frontend;
@endphp

@props ([
    'area',
    'containerClass' => null,
    'layout' => Frontend::layout(),
])

@include ('capell-layout-builder::components.layout.area', [
    'area' => $area,
    'containerClass' => $containerClass,
    'layout' => $layout,
])
