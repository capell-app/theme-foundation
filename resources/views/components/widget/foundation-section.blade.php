@if (is_string($sectionView))
    @if ($anchorable)
        <div
            id="{{ $sectionAnchor }}"
            class="scroll-mt-24"
        >
            @include($sectionView, ['section' => $section, 'formDeliveryFragmentUrl' => $formDeliveryFragmentUrl])
        </div>
    @else
        @include($sectionView, ['section' => $section, 'formDeliveryFragmentUrl' => $formDeliveryFragmentUrl])
    @endif
@endif
