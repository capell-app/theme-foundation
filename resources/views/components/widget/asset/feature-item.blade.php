@props ([
    'color',
    'column',
    'assetRenderData',
    'widget',
    'widgetAsset',
])

@php
    $linkedPageUrl = $assetRenderData->linkUrl;
    $image = $assetRenderData->image;
    $icon = $assetRenderData->icon;
    $textAlign = $assetRenderData->textAlign ?? ('text-left' . ((int) $column === 1 && $widget->image ? ' lg:text-right' : ''));
@endphp

<div
    @class ([
        'capell-asset-feature-item',
        'widget-features-item flex items-start gap-x-4 pt-1',
        'lg:flex-row-reverse lg:text-right' => (int) $column === 1 && $widget->image,
    ])
>
    @if ($icon)
        <div
            class="bg-gray flex h-14 w-14 shrink-0 items-center justify-center rounded-full p-3 dark:bg-gray-600"
        >
            @if ($linkedPageUrl)
                <a href="{{ $linkedPageUrl }}">
                    <x-capell::icon
                        :icon="$icon"
                        class="h-10 w-10 text-white"
                        loading="lazy"
                    />
                </a>
            @else
                <x-capell::icon
                    :icon="$icon"
                    class="h-10 w-10 text-white"
                    loading="lazy"
                />
            @endif
        </div>
    @elseif ($image)
        @if ($linkedPageUrl)
            <a href="{{ $linkedPageUrl }}">
                <x-capell::image-source
                    :image="$image"
                    :width="120"
                    :height="120"
                    :alt="$assetRenderData->title"
                    class="h-10 w-10 rounded-full object-cover object-center"
                    loading="lazy"
                />
            </a>
        @else
            <x-capell::image-source
                :image="$image"
                :width="120"
                :height="120"
                :alt="$assetRenderData->title"
                class="h-10 w-10 rounded-full object-cover object-center"
                loading="lazy"
            />
        @endif
    @endif

    @if ($assetRenderData->content || $assetRenderData->title)
        <x-capell::content
            :compact="true"
            :content="$assetRenderData->content"
            :content-type="$assetRenderData->contentStructure"
            :color="$color"
            :title="$assetRenderData->title"
            :heading-tag="$assetRenderData->headingSize"
            :heading-weight="$assetRenderData->headingWeight"
            :text-align="$textAlign"
            size="sm"
            class="prose-h3:mb-1 lg:prose-base lg:leading-snug"
        />
    @endif
</div>
