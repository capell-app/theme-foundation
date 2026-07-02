{{-- format-ignore-start --}}
@php
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Models\Page;
    use Capell\Frontend\Facades\Frontend;

    $site = Frontend::site();
    $theme = Frontend::theme();
@endphp
{{-- format-ignore-end --}}

@props([
    'assetRenderDataItems',
    'assets',
    'columns' => $container['meta']['override_columns'] ?? $widget->getMeta('columns', 3),
    'container',
    'containerKey',
    'containerWidth' => null,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'index',
    'loop',
    'size' => $widget->getMeta('size'),
    'widget',
])
@if ($assets->isNotEmpty() || ! config('capell-layout-builder.widget.skip_render_empty', true))
    <x-capell-theme-foundation::widget.wrapper
        class="capell-asset-accordion widget-section-grid widget-accordion space-y-6"
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :$widget
    >
        @if ($widget->translation)
            <x-capell::content
                class="mb-4"
                :compact="true"
                :content="$widget->translation->content"
                :content-type="$widget->type->content_structure"
                :divider="$widget->getMeta('content_divider')"
                :muted="in_array($containerKey, $theme->secondary_containers)"
                :title="$widget->translation->title"
                :text-align="$widget->getMeta('align')"
                :heading-size="$widget->getMeta('heading_size')"
                :heading-style="$widget->getMeta('heading_style')"
            />
        @endif

        @if ($assets->isNotEmpty())
            <div
                x-data="{
                    selected: 0,
                    isActive(index) {
                        return this.selected === index
                    },
                }"
                class="flex w-full flex-col divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-600 dark:border-gray-600"
            >
                @foreach ($assetRenderDataItems as $assetRenderDataItem)
                    {{-- format-ignore-start --}}
                @php
                    $widgetAsset = $assetRenderDataItem['widgetAsset'];
                    $assetRenderData = $assetRenderDataItem['renderData'];
                    $image = $assetRenderData->image;
                    $media = $image?->media;
                    $linkedPageUrl = $assetRenderData->linkUrl;
                    $actions = $assetRenderData->actions;
                @endphp
                {{-- format-ignore-end --}}
                    <section
                        class="widget-accordion-item flex flex-col gap-1 bg-gray-50 py-3 first:rounded-t-lg last:rounded-b-lg dark:bg-white/5"
                    >
                        <button
                            type="button"
                            x-on:click="
                                isActive({{ $loop->iteration }})
                                    ? (selected = null)
                                    : (selected = {{ $loop->iteration }})
                            "
                            class="hover:text-primary focus:text-primary group flex cursor-pointer items-center"
                        >
                            <div class="ml-2 flex w-10 justify-center">
                                @svg('heroicon-o-chevron-right', [
                                    'class' => 'text-link group-hover:text-primary group-focus:text-primary h-6 w-6',
                                    ':class' => "{ 'rotate-90': isActive(" . $loop->iteration . "), 'rotate-0': !isActive(" . $loop->iteration . ') }',
                                ])
                            </div>
                            <div class="font-medium">
                                {{ $assetRenderData->title }}
                            </div>
                        </button>

                        <div
                            x-bind:style="
                                isActive({{ $loop->iteration }})
                                    ? 'max-height: ' + $el.scrollHeight + 'px'
                                    : ''
                            "
                            class="relative max-h-0 overflow-hidden transition-all duration-700"
                        >
                            <div class="ml-4 px-1 pt-1 pr-4">
                                <div class="flex gap-6">
                                    @if ($assetRenderData->content)
                                        <x-capell::content
                                            :compact="true"
                                            :content="$assetRenderData->content"
                                            :content-type="$assetRenderData->contentStructure"
                                        />
                                    @endif

                                    @if ($media)
                                        @if ($linkedPageUrl)
                                            <a
                                                href="{{ $linkedPageUrl }}"
                                                @wireNavigate
                                                class="shrink-0"
                                            >
                                                <x-capell::media
                                                    :media="$media"
                                                    :width="120"
                                                    :height="120"
                                                    :alt="$assetRenderData->title"
                                                    fit="crop"
                                                    class="h-10 w-10 rounded-full object-cover object-center"
                                                    loading="lazy"
                                                />
                                            </a>
                                        @else
                                            <x-capell::media
                                                :media="$media"
                                                :width="120"
                                                :height="120"
                                                :alt="$assetRenderData->title"
                                                fit="crop"
                                                class="h-10 w-10 rounded-full object-cover object-center"
                                                loading="lazy"
                                            />
                                        @endif
                                    @endif
                                </div>

                                @if ($actions || $linkedPageUrl)
                                    <x-capell::actions
                                        :$actions
                                        class="mt-4"
                                    >
                                        @if ($linkedPageUrl)
                                            <x-capell::button
                                                :url="$linkedPageUrl"
                                                color="default"
                                                icon="heroicon-o-chevron-right"
                                            >
                                                {{ $assetRenderData->linkText }}
                                            </x-capell::button>
                                        @endif
                                    </x-capell::actions>
                                @endif
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </x-capell-theme-foundation::widget.wrapper>
@endif
