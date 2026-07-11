@php
    use Capell\Core\Actions\ResolveRenderableComponentAction;
    use Capell\Core\Enums\AssetComponentEnum;
    use Capell\Core\Enums\RenderableTypeEnum;
    use Capell\Core\Facades\CapellCore;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\View\PublicModelMeta;

    $language = Frontend::language();
    $page = Frontend::page();
    $theme = Frontend::theme();
@endphp

@props ([
    'assets' => collect(),
    'columns' => $container['meta']['override_columns'] ?? $widget->getMeta('columns', 3),
    'componentItem' => $widget->getMeta('component_item', AssetComponentEnum::Card->value),
    'container',
    'containerKey',
    'containerWidth' => null,
    'index',
    'loop',
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'size' => $widget->getMeta('size', $containerKey === 'sidebar' ? 'sm' : null),
    'spacing' => $widget->getMeta('spacing', $containerKey === 'sidebar' ? 'md' : 'lg'),
    'widget',
    'withChildCount' => (bool) $widget->getMeta('with_child_count'),
    'withDate' => (bool) $widget->getMeta('with_date'),
    'withImage' => (bool) $widget->getMeta('with_image'),
    'withParent' => (bool) $widget->getMeta('with_parent'),
    'withSummary' => (bool) $widget->getMeta('with_summary'),
])
@php
    $pages ??= $assets
        ->map(fn (object $widgetAsset): ?object => method_exists($widgetAsset, 'getRelations') ? ($widgetAsset->getRelations()['asset'] ?? null) : null)
        ->filter()
        ->values();
    $isArticleListWidget = in_array($widget->key, ['latest-articles', 'popular-articles'], true);
    $currentPageRelations = method_exists($page, 'getRelations') ? $page->getRelations() : [];
    $currentPageTranslation = $currentPageRelations['translation'] ?? null;
    $currentPageType = $currentPageRelations['type'] ?? null;

    if ($componentItem === 'capell::list.item') {
        $componentItem = AssetComponentEnum::Card->value;
    }

    $componentItem = ResolveRenderableComponentAction::run(RenderableTypeEnum::Asset, $componentItem);
@endphp

<div class="capell-asset-pages contents">
    @if ($pages->isNotEmpty() || ! config('capell-layout-builder.widget.skip_render_empty', true))
        <x-capell-theme-foundation::widget.wrapper
            class="widget-pages widget-pages"
            :container-class="$isArticleListWidget && $containerKey === 'sidebar' ? 'space-y-3' : 'space-y-4'"
            :$container
            :$containerKey
            :$containerWidth
            :index="$loop->index"
            :$widget
        >
            @php
                $showTitle = $widget->getMeta("container_options.{$containerKey}.hide_title") !== true
                    && ($widget->translation?->title || ($showPageTitle && $currentPageTranslation?->title));
                $showContent = $widget->getMeta("container_options.{$containerKey}.hide_content") !== true
                    && ($widget->translation?->content || ($showPageContent && $currentPageTranslation?->content));
            @endphp

            @if ($showTitle || $showContent)
                <x-capell::content
                    class="widget-content"
                    :compact="true"
                    :content="$showContent ? ($widget->translation->content ?: ($showPageContent ? $currentPageTranslation?->content : null)) : null"
                    :content-type="$widget->translation->content ? $widget->blueprint->content_structure : ($showPageContent ? $currentPageType?->content_structure : null)"
                    :divider="$widget->getMeta('content_divider')"
                    :muted="in_array($containerKey, $theme->secondary_containers)"
                    :text-align="$widget->getMeta('align')"
                    :title="$showTitle ? ($widget->translation->title ?: ($showPageTitle ? $currentPageTranslation?->title : null)) : null"
                    :heading-style="$widget->getMeta('heading_style')"
                    :heading-tag="$showPageTitle ? 'h1' : null"
                />
            @endif

            @if (! $pages || $pages->isEmpty())
                <x-capell::no-results>
                    {!! $widget->translation?->getMeta('no_results') ?: __('capell-layout-builder::generic.no_pages_found') !!}
                </x-capell::no-results>
            @else
                <div
                    @class ([
                        'grid',
                        ...($isArticleListWidget && $columns <= 1
                        ? [
                            'divide-y divide-slate-200/45 dark:divide-slate-700/70',
                        ]
                        : ($containerKey === 'sidebar' && (! $columns && $columns !== 0)
                        ? [
                            'divide-y divide-gray-100 [&>*:not(:first-child)]:pt-4 [&>*:not(:last-child)]:pb-4',
                        ]
                        : [
                            '[&>*:not(:first-child)]:pt-2 [&>*:not(:last-child)]:pb-2' => $spacing === 'sm' && (! $columns && $columns !== 0),
                            '[&>*:not(:first-child)]:pt-4 [&>*:not(:last-child)]:pb-4' => $spacing === 'lg' && (! $columns && $columns !== 0),
                            '[&>*:not(:first-child)]:pt-6 [&>*:not(:last-child)]:pb-6' => $spacing === 'md' && (! $columns && $columns !== 0),
                            'gap-2 @lg:gap-x-4 @lg:gap-y-4' => $spacing === 'sm' && $columns,
                            'gap-6 @lg:gap-x-8 @lg:gap-y-8' => $spacing === 'md' && $columns,
                            'gap-8 @lg:gap-x-10 @lg:gap-y-10' => $spacing === 'lg' && $columns,
                            '@3xl:grid-cols-2' => $columns > 1 && count($pages) >= 2,
                            '@8xl:grid-cols-3' => $columns > 2 && count($pages) >= 3,
                        ])),
                    ])
                >
                    @foreach ($pages as $item)
                        @php
                            $itemRelations = method_exists($item, 'getRelations') ? $item->getRelations() : [];
                            $itemImage = $withImage ? ($itemRelations['image'] ?? null) : null;
                            $itemPageUrl = $itemRelations['pageUrl'] ?? null;
                            $itemParent = $withParent ? ($itemRelations['parent'] ?? null) : null;
                            $itemTranslation = $itemRelations['translation'] ?? null;
                        @endphp

                        @if ($isArticleListWidget)
                            @php
                                $itemImage = $withImage ? PublicModelMeta::get($item, 'image_source') : null;
                                $itemDate = $withDate ? $item->getPublishDate() : null;
                            @endphp

                            <article
                                @class ([
                                    'latest-articles-page-item group/latest grid min-w-0 first:pt-0 last:pb-0',
                                    'gap-3 py-4 @3xl:py-5' => $containerKey !== 'sidebar',
                                    'gap-2.5 py-5' => $containerKey === 'sidebar',
                                ])
                            >
                                @if ($itemImage)
                                    <a
                                        href="{{ $itemPageUrl?->full_url }}"
                                        title="{{ htmlspecialchars(strip_tags($itemTranslation?->title ?? '')) }}"
                                        @class ([
                                            'widget min-w-0 overflow-hidden after:!hidden after:!content-none',
                                            'aspect-[16/8] rounded-md bg-slate-100 dark:bg-slate-800' => $containerKey !== 'sidebar',
                                            'aspect-[16/7] rounded-[4px]' => $containerKey === 'sidebar',
                                        ])
                                        @wireNavigate
                                    >
                                        <x-capell::image-source
                                            :image="$itemImage"
                                            loading="lazy"
                                            :alt="$itemTranslation?->title"
                                            :width="320"
                                            :height="240"
                                            sizes="{{ $containerKey === 'sidebar' ? '(min-width: 1024px) 16rem, 92vw' : '(min-width: 1024px) 18rem, 92vw' }}"
                                            class="h-full w-full object-cover object-center transition duration-500 group-hover/latest:scale-[1.04]"
                                        />
                                    </a>
                                @endif

                                <div class="flex min-w-0 flex-col gap-2">
                                    <a
                                        href="{{ $itemPageUrl?->full_url }}"
                                        title="{{ htmlspecialchars(strip_tags($itemTranslation?->title ?? '')) }}"
                                        @class ([
                                            'hover:text-primary focus:text-primary line-clamp-3 leading-snug font-semibold text-slate-950 no-underline transition after:!hidden after:!content-none dark:text-white',
                                            'text-[0.95rem]' => $containerKey !== 'sidebar',
                                            'text-[0.92rem]' => $containerKey === 'sidebar',
                                        ])
                                        @wireNavigate
                                    >
                                        {{ $itemTranslation?->title }}
                                    </a>

                                    @if ($withSummary && $itemTranslation?->summary)
                                        <p
                                            @class ([
                                                'line-clamp-2 text-slate-600 dark:text-slate-300',
                                                'text-sm leading-6' => $containerKey !== 'sidebar',
                                                'text-[0.86rem] leading-5' => $containerKey === 'sidebar',
                                            ])
                                        >
                                            {{ $itemTranslation->summary }}
                                        </p>
                                    @endif

                                    @if ($itemDate)
                                        <time
                                            class="pt-1 text-xs font-medium tracking-[0.08em] text-slate-500 uppercase dark:text-slate-400"
                                            datetime="{{ $itemDate->toW3cString() }}"
                                        >
                                            {{ $itemDate->format(config('capell-frontend.date_format')) }}
                                        </time>
                                    @endif
                                </div>
                            </article>
                        @else
                            <x-dynamic-component
                                :component="$componentItem"
                                :class="$widget->key . '-page-item'"
                                :$container
                                :$containerKey
                                :count="$withChildCount ? $item->children_count : null"
                                :icon="(bool) $widget->getMeta('icon')"
                                :image="$itemImage"
                                :$loop
                                :parent="$itemParent"
                                :publish-date="$withDate ? $item->getPublishDate() : null"
                                :$size
                                :summary="$itemTranslation?->summary"
                                :title="$itemTranslation?->title"
                                :url="$itemPageUrl?->full_url"
                                :$withSummary
                            />
                        @endif
                    @endforeach
                </div>

                @if (method_exists($pages, 'total'))
                    <x-capell::pagination :results="$pages" />
                @endif
            @endif
        </x-capell-theme-foundation::widget.wrapper>
    @endif
</div>
