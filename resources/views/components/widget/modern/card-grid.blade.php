@props ([
    'assetRenderDataItems',
    'assets',
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
    class="capell-modern-card-grid widget-ap-card-grid"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="ap-showcase-card-grid capell-showcase">
        <div class="capell-showcase__inner">
            @if ($widget->translation)
                <div class="capell-showcase__section-head">
                    @if ($widget->translation->title)
                        <h2
                            class="ap-card-grid-headline capell-showcase__heading"
                        >
                            {{ $widget->translation->title }}
                        </h2>
                    @endif

                    @if ($widget->translation->content)
                        <p
                            class="ap-card-grid-description capell-showcase__copy"
                        >
                            {!! strip_tags($widget->translation->content) !!}
                        </p>
                    @endif
                </div>
            @endif

            <div
                class="ap-card-grid {{ $responsiveGrid }}"
                style="--ap-card-columns: {{ max(1, min(4, $columns)) }}"
            >
                @if ($assets->isNotEmpty())
                    @foreach ($assetRenderDataItems as $assetRenderDataItem)
                        @php
                            $assetRenderData = $assetRenderDataItem['renderData'];
                            $icon = $assetRenderData->icon ?? '';
                            $accent = $assetRenderData->accent ?? 'teal';
                            $role = $assetRenderData->role ?? 'card';
                            $cardTitle = $assetRenderData->caption ?? $assetRenderData->title;
                        @endphp

                        <article
                            class="ap-card layout-builder-card {{ $responsiveItem }}"
                            data-accent="{{ $accent }}"
                            data-role="{{ $role }}"
                        >
                            @if ($icon !== '')
                                <span class="ap-card__icon">
                                    @if (str_starts_with($icon, 'heroicon-'))
                                        @svg ($icon, 'h-5 w-5')
                                    @else
                                        {{ $icon }}
                                    @endif
                                </span>
                            @endif

                            @if ($cardTitle)
                                <h3 class="ap-card-title ap-card__title">
                                    {{ $cardTitle }}
                                </h3>
                            @endif

                            @if ($assetRenderData->content)
                                <p
                                    class="ap-card-description ap-card__description"
                                >
                                    {{ strip_tags($assetRenderData->content) }}
                                </p>
                            @endif

                            @if (($assetRenderData->meta['link_text'] ?? null) && ($assetRenderData->meta['link_url'] ?? null))
                                <a
                                    href="{{ $assetRenderData->meta['link_url'] }}"
                                    class="ap-card-link ap-card__link"
                                >
                                    <span>
                                        {{ $assetRenderData->meta['link_text'] }}
                                    </span>
                                    @svg ('heroicon-o-arrow-right', 'h-4 w-4')
                                </a>
                            @endif
                        </article>
                    @endforeach
                @elseif ($widget->getMeta('cards'))
                    @foreach ($widget->getMeta('cards') as $card)
                        <article
                            class="ap-card layout-builder-card {{ $responsiveItem }}"
                        >
                            @if (! empty($card['icon']))
                                <span class="ap-card__icon">
                                    {{ $card['icon'] }}
                                </span>
                            @endif

                            @if (! empty($card['title']))
                                <h3 class="ap-card-title ap-card__title">
                                    {{ $card['title'] }}
                                </h3>
                            @endif

                            @if (! empty($card['description']))
                                <p
                                    class="ap-card-description ap-card__description"
                                >
                                    {{ $card['description'] }}
                                </p>
                            @endif
                        </article>
                    @endforeach
                @endif
            </div>
        </div>
    </section>
</x-capell-theme-foundation::widget.wrapper>
