@props ([
    'ariaLabel' => null,
    'buttonIcon' => '',
    'class' => '',
    'color' => 'default',
    'disabled' => false,
    'icon' => '',
    'icon_color' => '',
    'icon_position' => '',
    'loading' => false,
    'loadingLabel' => null,
    'outline' => false,
    'size' => 'md',
    'target' => '',
    'title' => '',
    'type' => 'button',
    'url' => '',
    'weight' => '',
    'wireNavigation' => true,
])

@php
    use Capell\Core\Support\Security\PublicUrlSanitizer;
    use Capell\Frontend\Facades\Frontend;

    try {
        $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    } catch (Throwable) {
        $runtimeManifest = null;
    }

    $safeUrl = PublicUrlSanitizer::sanitize($url);
    $resolvedColor = in_array($color, ['default', 'primary', 'secondary', 'light'], true)
        ? $color
        : 'default';
    $resolvedSize = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $resolvedType = in_array($type, ['button', 'submit', 'reset'], true) ? $type : 'button';
    $resolvedTarget = in_array($target, ['_blank', '_self', '_parent', '_top'], true)
        ? $target
        : null;
    $isDisabled = (bool) $disabled || (bool) $loading;
    $isExternalUrl = $safeUrl !== null && preg_match('/^https?:\/\//i', $safeUrl) === 1;
    $usesWireNavigation = (bool) $wireNavigation
        && ! $isDisabled
        && ! $isExternalUrl
        && ($runtimeManifest?->usesWireNavigate ?? false);
    $resolvedLoadingLabel = filled($loadingLabel)
        ? (string) $loadingLabel
        : __('capell-theme-foundation::generic.loading');
    $resolvedRel = $resolvedTarget === '_blank' ? 'noopener noreferrer' : null;
@endphp

@if (! $buttonIcon && $icon)
    @capellBuffer ($buttonIcon)
        <x-dynamic-component
            :component="$icon"
            @class([
                'capell-button__icon',
                'h-5 w-5' => $resolvedSize === 'lg',
                'h-4 w-4' => $resolvedSize === 'md',
                'h-3.5 w-3.5' => $resolvedSize === 'sm',
                'stroke-current' => ! $icon_color,
                'stroke-secondary' => $icon_color === 'secondary',
                'stroke-primary' => $icon_color === 'primary',
            ])
            aria-hidden="true"
        />
    @endcapellBuffer
@endif

@php
    $buttonClasses = [
        'capell-button',
        "capell-button--{$resolvedColor}",
        "capell-button--{$resolvedSize}",
        'capell-button--outline' => (bool) $outline,
        'capell-button--loading' => (bool) $loading,
        'font-light' => $weight === 'light',
        'font-bold' => $weight === 'bold',
        'font-semibold' => ! in_array($weight, ['light', 'bold'], true),
        $class,
    ];
@endphp

@if ($safeUrl !== null)
    <a
        @if (! $isDisabled) href="{{ $safeUrl }}" @endif
        @if (filled($title)) title="{{ strip_tags((string) $title) }}" @endif
        @if (filled($ariaLabel)) aria-label="{{ $ariaLabel }}" @endif
        @if ($isDisabled) aria-disabled="true" tabindex="-1" @endif
        @if ($loading) aria-busy="true" @endif
        @if ($resolvedTarget !== null && ! $isDisabled) target="{{ $resolvedTarget }}" @endif
        @if ($resolvedRel !== null && ! $isDisabled) rel="{{ $resolvedRel }}" @endif
        @if ($usesWireNavigation) @wireNavigate @endif
        {{ $attributes->class($buttonClasses) }}
    >
        @if ($loading)
            <span
                class="capell-button__spinner"
                aria-hidden="true"
            ></span>
        @elseif ($buttonIcon && $icon_position !== 'after')
            {{ $buttonIcon() }}
        @endif

        <span class="capell-button__label">
            @if ($loading)
                {{ $resolvedLoadingLabel }}
            @else
                {{ $slot }}
            @endif
        </span>

        @if (! $loading && $buttonIcon && $icon_position === 'after')
            {{ $buttonIcon() }}
        @endif
    </a>
@else
    <button
        type="{{ $resolvedType }}"
        @if (filled($title)) title="{{ strip_tags((string) $title) }}" @endif
        @if (filled($ariaLabel)) aria-label="{{ $ariaLabel }}" @endif
        @if ($isDisabled) disabled aria-disabled="true" @endif
        @if ($loading) aria-busy="true" @endif
        {{ $attributes->class($buttonClasses) }}
    >
        @if ($loading)
            <span
                class="capell-button__spinner"
                aria-hidden="true"
            ></span>
        @elseif ($buttonIcon && $icon_position !== 'after')
            {{ $buttonIcon() }}
        @endif

        <span class="capell-button__label">
            @if ($loading)
                {{ $resolvedLoadingLabel }}
            @else
                {{ $slot }}
            @endif
        </span>

        @if (! $loading && $buttonIcon && $icon_position === 'after')
            {{ $buttonIcon() }}
        @endif
    </button>
@endif
