@php
    use Capell\Core\Enums\ContentStructure;
    use Capell\Frontend\Actions\GetPageVariablesAction;
    use Capell\Frontend\Actions\RenderHtmlContentAction;
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'align' => '',
    'contentType' => ContentStructure::Html,
    'color' => '',
    'compact' => false,
    'content' => '',
    'divider' => null,
    'headingSize' => null,
    'headingTag' => null,
    'headingStyle' => null,
    'headingWeight' => 'normal',
    'headingBalance' => true,
    'image' => null,
    'muted' => null,
    'language' => null,
    'layout' => null,
    'pageRecord' => null,
    'site' => null,
    'size' => '',
    'textAlign' => 'left',
    'theme' => null,
    'title' => '',
    'imageTitle' => null,
    'urlParams' => null,
    'width' => 'full',
])

@php
    $page = $pageRecord;

    if ($page === null) {
        try {
            $page = Frontend::page();
        } catch (Throwable) {
            $page = null;
        }
    }

    if ($language === null) {
        try {
            $language = Frontend::language();
        } catch (Throwable) {
            $language = null;
        }
    }

    if ($site === null) {
        try {
            $site = Frontend::site();
        } catch (Throwable) {
            $site = null;
        }
    }

    if ($layout === null) {
        try {
            $layout = Frontend::layout();
        } catch (Throwable) {
            $layout = null;
        }
    }

    if ($theme === null) {
        try {
            $theme = Frontend::theme();
        } catch (Throwable) {
            $theme = null;
        }
    }

    $imageWidth = 360;
    $imageHeight = null;

    if ($image) {
        $sourceWidth = max(1, (int) $image->getWidth());
        $sourceHeight = max(1, (int) $image->getHeight());
        $imageHeight = (int) round($imageWidth * ($sourceHeight / $sourceWidth));
    }

    if (! $headingSize && ! $headingTag) {
        $headingSize = $muted ? $headingTag = 'h4' : $headingTag = 'h3';
    }

    if (! $headingTag) {
        $headingTag = $headingSize;
    }

    if (! $muted && $headingStyle === 'secondary') {
        $muted = true;
    }

    $pageVariables = GetPageVariablesAction::run(
        $page,
        $site,
        is_array($urlParams) ? $urlParams : [],
    );
    $translationVariables = collect($pageVariables)
        ->filter(fn (mixed $value): bool => is_scalar($value) || $value instanceof Stringable)
        ->map(fn (mixed $value): string => (string) $value)
        ->all();

    if (is_string($content)) {
        $content = __($content, $translationVariables);
    }

    $title = __($title, $translationVariables);
@endphp

<div
    {{
        $attributes->class([
            'content-component prose prose-h1:font-bold [&>:first-child]:mt-0 [&>:last-child]:mb-0',
            'prose-invert' => $color === 'light' && ($theme?->withDarkMode ?? false),
            'dark:prose-invert' => $color !== 'light' && ($theme?->withDarkMode ?? false),
            'prose-muted' => $color === 'muted' || (! $color && $muted),
            'max-w-none' => $width === 'full',
            'mx-auto' => $align === 'center' || (! $align && $textAlign === 'center'),
            'prose-lg md:prose-xl lg:prose-2xl xl:prose-4xl' => $size === 'lg',
            'prose-sm' => $size === 'sm',
            'prose-compact' => $compact,
            'prose-headings:text-balance' => $headingBalance,
            'prose-headings:font-medium' => $headingWeight === 'medium',
            'prose-headings:font-normal' => $headingWeight === 'normal',
            'text-left' => $textAlign === 'left',
            'text-right' => $textAlign === 'right',
            'text-center' => $textAlign === 'center',
            $textAlign => ! in_array($textAlign, ['left', 'right', 'center'], true),
        ])
    }}
>
    @if ($image)
        {{-- format-ignore-start --}}
        <x-capell::media
                :media="$image"
                fit="crop"
                :width="$imageWidth"
                :height="$imageHeight"
                data-group="gallery"
                :data-title="$imageTitle ?? $title"
                :data-lightbox="$image->getFullUrl()"
                role="button"
                tabindex="0"
                onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); this.click(); }"
                aria-label="{{ __('capell-frontend::generic.open_image') }}: {{ $imageTitle ?? $title }}"
                :alt="$imageTitle ?? $title"
                fetchpriority="high"
                @class([
                    'h-auto object-cover object-center lightbox cursor-pointer md:float-right md:max-w-[40%] md:ml-10 md:mt-0',
                    'rounded' => (bool) $theme?->getMeta('rounded_images'),
                ])
                loading="eager"
                sizes="(min-width: 768px) 40vw, 88vw"
        />
        {{-- format-ignore-end --}}
    @endif

    @if ($divider === 'above_heading' && $title)
        <div
            aria-hidden="true"
            class="not-prose mb-4 border-t"
            style="border-color: var(--color-divider)"
        ></div>
    @endif

    @if ($title)
        {{-- format-ignore-start --}}
        <{{ $headingTag }}
            @class([
                'font-medium mb-4 not-prose text-secondary',
                'text-secondary' => $headingStyle === 'secondary',
                'text-4xl' => $headingSize === 'h1',
                'text-3xl' => $headingSize === 'h2',
                'text-2xl' => $headingSize === 'h3',
                'text-xl' => $headingSize === 'h4',
                'text-lg' => $headingSize === 'h5',
                'text-base' => $headingSize === 'h6',
                'font-medium' => $headingWeight === 'medium',
                'font-normal' => $headingWeight !== 'medium',
                'text-balance' => $headingBalance,
            ])
        >
            {{ $title }}
        </{{ $headingTag }}>
        {{-- format-ignore-end --}}
    @endif

    @if ($divider === 'below_heading' && $title)
        <div
            aria-hidden="true"
            class="not-prose mb-4 border-t"
            style="border-color: var(--color-divider)"
        ></div>
    @endif

    @if ($contentType === ContentStructure::Blocks)
        <x-capell-layout-builder::layout-widgets
            :widgets="$content"
            :layout="$layout"
            :page="$page"
        />
    @else
        {!! RenderHtmlContentAction::run($content, $pageVariables) !!}
    @endif

    {{ $slot ?? '' }}

    @if ($divider === 'below_content')
        <div
            aria-hidden="true"
            class="not-prose mt-4 border-t"
            style="border-color: var(--color-divider)"
        ></div>
    @endif
</div>
