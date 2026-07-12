@props ([
    'backgroundColor' => $widget->getMeta('background_color'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'content' => $widget->translation?->content,
    'headingSize' => $widget->getMeta('heading_size', 'h2'),
    'loop',
    'reverseOrder' => $widget->getMeta('reverse_order'),
    'rounded' => (bool) $widget->getMeta('rounded_images'),
    'size' => $widget->getMeta('size'),
    'title' => $widget->translation?->title,
    'widget',
])
{{-- format-ignore-start --}}
@php
    use Capell\Core\Enums\ContainerWidthEnum;use Capell\FoundationTheme\Actions\BuildBannerImageRenderDataAction;use Capell\Frontend\Facades\Frontend;

    $theme = Frontend::theme();

    /**
    * @var \Capell\LayoutBuilder\Models\Widget $widget
    */
    $renderData = BuildBannerImageRenderDataAction::run($widget, $content, $title, $rounded, $reverseOrder);
    $backgroundImage = $renderData->backgroundImage;
    $actions = $renderData->actions;
    $hasContent = $renderData->hasContent;
    $imgRounded = $renderData->imageRoundedClass;
@endphp
{{-- format-ignore-end --}}

<x-capell-theme-foundation::widget.wrapper
    class="capell-widget-banner-image widget-banner-image relative"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :background-color="$backgroundColor"
    :$widget
    :container-width="ContainerWidthEnum::Full"
>
    @if ($backgroundImage)
        <div
            @class ([
                'w-full',
                'md:w-1/2' => $hasContent,
                'md:absolute' => $hasContent,
                'md:inset-y-0' => $hasContent,
                'md:left-0' => $hasContent && $reverseOrder,
                'md:right-0' => $hasContent && ! $reverseOrder,
            ])
        >
            <x-capell::media
                :media="$backgroundImage"
                size="xxl"
                :rounded="false"
                :alt="$hasContent ? '' : null"
                :class="'h-auto w-full object-cover md:h-full' . $imgRounded"
                :aria-hidden="$hasContent ? 'true' : null"
            />
        </div>
    @endif

    @if ($hasContent)
        <div
            @class ([
                'container',
                'z-10',
                'absolute inset-0 flex items-end' => $backgroundImage,
                'relative flex flex-col' => ! $backgroundImage,
                'md:relative md:flex md:flex-col md:items-center',
                'gap-y-6',
                'gap-x-6',
                'py-10',
                'md:flex-row-reverse' => $reverseOrder,
                'md:flex-row' => ! $reverseOrder,
            ])
        >
            <div
                @class ([
                    'w-full',
                    'md:w-1/2' => $backgroundImage,
                    'md:pl-10' => $backgroundImage && $reverseOrder,
                    'md:pr-10' => $backgroundImage && ! $reverseOrder,
                ])
            >
                <div
                    @class ([
                        'rounded p-6' => $backgroundImage && $hasContent,
                        'bg-white/90 shadow-sm backdrop-blur' => $backgroundImage && $hasContent,
                    ])
                >
                    @if ($content || $title)
                        <x-capell::content
                            class="mb-2"
                            :compact="true"
                            :content="$content"
                            :content-type="$widget->blueprint->content_structure"
                            :divider="$widget->getMeta('content_divider')"
                            :heading-size="$headingSize"
                            :title="$title"
                            :text-align="$widget->getMeta('align')"
                            :heading-style="$widget->getMeta('heading_style')"
                        />
                    @endif

                    @if ($actions)
                        <x-capell::actions
                            class="mt-4"
                            :$actions
                        />
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-capell-theme-foundation::widget.wrapper>
