@if (is_string($sectionView))
    @if ($anchorable)
        <div
            id="{{ $sectionAnchor }}"
            class="scroll-mt-24"
        >
            @include ($sectionView, ['section' => $section])
        </div>
    @else
        @include ($sectionView, ['section' => $section])
    @endif
@endif
