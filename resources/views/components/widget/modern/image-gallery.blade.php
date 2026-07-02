@props([
    'assetRenderDataItems',
    'assets',
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'columns' => (int) ($widget->getMeta('columns', 3)),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

@php
    $responsiveGrid = '!flex snap-x [scrollbar-width:none] gap-4 !overflow-x-auto pb-3 md:!grid md:!overflow-visible md:pb-0 [&::-webkit-scrollbar]:hidden';
    $responsiveItem = 'min-w-full snap-start md:min-w-0';
@endphp

<x-capell-theme-foundation::widget.wrapper
    class="capell-modern-image-gallery widget-ap-image-gallery"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="ap-showcase-gallery capell-showcase">
        <div class="capell-showcase__inner">
            @if ($title || $content)
                <div class="capell-showcase__section-head">
                    @if ($title)
                        <h2
                            class="ap-gallery-headline capell-showcase__heading"
                        >
                            {{ $title }}
                        </h2>
                    @endif

                    @if ($content)
                        <p class="ap-gallery-description capell-showcase__copy">
                            {!! strip_tags($content) !!}
                        </p>
                    @endif
                </div>
            @endif

            @if ($assets->isNotEmpty())
                <div
                    class="ap-gallery-grid {{ $responsiveGrid }}"
                    style="
                        --ap-gallery-columns: {{ max(1, min(4, $columns)) }};
                    "
                >
                    @foreach ($assetRenderDataItems as $assetRenderDataItem)
                        @php
                            $assetRenderData = $assetRenderDataItem['renderData'];
                            $media = $assetRenderData->image?->media;
                            $role = $assetRenderData->role ?? 'gallery-item';
                            $accent = $assetRenderData->accent ?? 'teal';
                            $caption = $assetRenderData->caption ?? $assetRenderData->title;
                            $cropPreset = $assetRenderData->cropPreset;
                        @endphp

                        @if ($media)
                            <figure
                                class="ap-gallery-item {{ $responsiveItem }}"
                                data-accent="{{ $accent }}"
                                data-role="{{ $role }}"
                            >
                                <x-capell::media
                                    :media="$media"
                                    :alt="$caption"
                                    :size="$cropPreset"
                                    class="h-full w-full object-cover"
                                    height="240"
                                    loading="lazy"
                                    sizes="(min-width: 768px) 33vw, 88vw"
                                    width="320"
                                />
                                <figcaption class="ap-gallery-caption">
                                    <span>{{ $caption }}</span>
                                    @svg('heroicon-o-arrows-pointing-out', 'h-4 w-4 text-slate-400')
                                </figcaption>
                            </figure>
                        @else
                            @php
                                $icon = $assetRenderData->icon ?? 'heroicon-o-squares-2x2';
                            @endphp

                            <figure
                                class="ap-gallery-item ap-gallery-item--placeholder {{ $responsiveItem }}"
                                data-accent="{{ $accent }}"
                                data-role="{{ $role }}"
                            >
                                <div class="ap-gallery-placeholder">
                                    @if (str_starts_with((string) $icon, 'heroicon-'))
                                        @svg($icon, 'h-8 w-8')
                                    @else
                                        <span>{{ $icon }}</span>
                                    @endif
                                    <strong>{{ $caption }}</strong>
                                    @if ($assetRenderData->content)
                                        <span>
                                            {{ strip_tags($assetRenderData->content) }}
                                        </span>
                                    @endif
                                </div>
                                <figcaption class="ap-gallery-caption">
                                    <span>{{ $caption }}</span>
                                    @svg('heroicon-o-arrows-pointing-out', 'h-4 w-4 text-slate-400')
                                </figcaption>
                            </figure>
                        @endif
                    @endforeach
                </div>
            @elseif ($widget->image)
                <div
                    class="ap-gallery-grid {{ $responsiveGrid }}"
                    style="
                        --ap-gallery-columns: {{ max(1, min(4, $columns)) }};
                    "
                >
                    <figure class="ap-gallery-item {{ $responsiveItem }}">
                        <x-capell::media
                            :media="$widget->image"
                            :alt="$widget->image->name"
                            class="h-full w-full object-cover"
                            height="240"
                            loading="lazy"
                            sizes="(min-width: 768px) 33vw, 88vw"
                            width="320"
                        />
                        <figcaption class="ap-gallery-caption">
                            <span>{{ $widget->image->name }}</span>
                            @svg('heroicon-o-arrows-pointing-out', 'h-4 w-4 text-slate-400')
                        </figcaption>
                    </figure>
                </div>
            @endif
        </div>
    </section>
</x-capell-theme-foundation::widget.wrapper>
