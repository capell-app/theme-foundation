@props ([
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'primaryButtonText' => $widget->getMeta('primary_button_text'),
    'primaryButtonUrl' => $widget->getMeta('primary_button_url', '#'),
    'secondaryButtonText' => $widget->getMeta('secondary_button_text'),
    'secondaryButtonUrl' => $widget->getMeta('secondary_button_url', '#'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'hasPrimaryHeading' => false,
    'loop',
    'widget',
])

@php
    use Capell\FoundationTheme\Actions\BuildHeroRailItemsRenderDataAction;
    use Capell\FoundationTheme\Actions\MarkPrimaryHeadingRenderedAction;
    use Capell\Frontend\Facades\Frontend;

    $page = Frontend::page();
    $pageMeta = is_array($page?->meta) ? $page->meta : [];
    $heroStyle = (string) data_get($pageMeta, 'hero_style', 'default');
    $heroStyle = in_array($heroStyle, ['default', 'editorial', 'immersive', 'compact'], true) ? $heroStyle : 'default';
    $configuredHeroHeight = (string) data_get($pageMeta, 'hero_height', '');
    $heroHeight = preg_match('/^[a-zA-Z0-9\s().,%+-]+$/', $configuredHeroHeight) === 1 ? $configuredHeroHeight : null;
    $heroAssetSource = (string) data_get($pageMeta, 'hero_asset_source', 'widget');
    $backgroundImage = $widget->backgroundImage ?? $widget->image;
    $backgroundImageUrl = $backgroundImage?->getAvailableFullUrl(['large']);
    $heroStyleRules = [];

    if ($backgroundImageUrl) {
        $heroStyleRules[] = "background-image: url('{$backgroundImageUrl}');";
        $heroStyleRules[] = 'background-size: cover;';
        $heroStyleRules[] = 'background-position: center;';
    }

    if ($heroHeight) {
        $heroStyleRules[] = "--ap-hero-min-height: {$heroHeight};";
    }

    $heroStyleAttribute = implode(' ', $heroStyleRules);
    $heroItems = BuildHeroRailItemsRenderDataAction::run($widget, $page, $heroAssetSource);

    $configuredHeadingTag = (string) $widget->getMeta('heading_tag', '');
    $headingTag = in_array($configuredHeadingTag, ['h1', 'h2', 'h3'], true)
        ? $configuredHeadingTag
        : ($hasPrimaryHeading ? 'h2' : 'h1');

    if ($title && $headingTag === 'h1') {
        MarkPrimaryHeadingRenderedAction::run();
    }
@endphp

<x-capell-theme-foundation::widget.wrapper
    class="capell-modern-hero-banner widget-ap-hero-banner"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        class="ap-hero capell-showcase relative overflow-hidden"
        data-hero-style="{{ $heroStyle }}"
        @if ($heroStyleAttribute !== '')
            style="{{ $heroStyleAttribute }}"
        @endif
    >
        <div class="ap-hero__overlay"></div>

        <div class="ap-hero__inner capell-showcase__inner">
            <div class="ap-hero__content">
                @if ($title)
                    <{{ $headingTag }}
                        class="ap-hero__title capell-showcase__heading ap-headline"
                    >
                        {{ $title }}
                    </{{ $headingTag }}>
                @endif

                @if ($content)
                    <p class="ap-hero__copy capell-showcase__copy">
                        {!! strip_tags($content) !!}
                    </p>
                @endif

                @if ($primaryButtonText || $secondaryButtonText)
                    <div class="ap-hero__actions">
                        @if ($primaryButtonText)
                            <a
                                href="{{ $primaryButtonUrl }}"
                                class="ap-hero__button ap-hero__button--primary ap-cta-primary"
                            >
                                <span>{{ $primaryButtonText }}</span>
                                @svg ('heroicon-o-arrow-right', 'h-4 w-4')
                            </a>
                        @endif

                        @if ($secondaryButtonText)
                            <a
                                href="{{ $secondaryButtonUrl }}"
                                class="ap-hero__button ap-hero__button--secondary ap-cta-secondary"
                            >
                                <span>{{ $secondaryButtonText }}</span>
                                @svg ('heroicon-o-code-bracket', 'h-4 w-4')
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div
                class="ap-hero__product"
                aria-hidden="true"
            >
                <div class="ap-hero__panel">
                    <div class="ap-hero__panel-header">
                        <span class="ap-hero__panel-title">
                            {{ __('capell-theme-foundation::generic.hero_panel_title') }}
                        </span>
                        <span class="ap-hero__panel-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </div>

                    <div class="ap-hero__panel-body">
                        @forelse ($heroItems as $heroItem)
                            @php
                                $icon = $heroItem->icon ?? 'heroicon-o-squares-2x2';
                                $status = $heroItem->status ?? __('capell-theme-foundation::generic.hero_item_status_ready');
                                $caption = $heroItem->caption ?? $heroItem->title;
                                $isBlue = $loop->even;
                            @endphp

                            <div class="ap-hero__rail">
                                <span class="ap-hero__rail-icon">
                                    @if (str_starts_with((string) $icon, 'heroicon-'))
                                        @svg ($icon, 'h-5 w-5')
                                    @else
                                        <span>{{ $icon }}</span>
                                    @endif
                                </span>
                                <span>
                                    <span class="ap-hero__rail-title">
                                        {{ $caption }}
                                    </span>
                                    <span class="ap-hero__rail-copy">
                                        {{ strip_tags((string) $heroItem->content) }}
                                    </span>
                                </span>
                                <span
                                    @class ([
                                        'ap-hero__rail-status',
                                        'ap-hero__rail-status--blue' => $isBlue,
                                        'ap-hero__rail-status--green' => ! $isBlue,
                                    ])
                                >
                                    {{ $status }}
                                </span>
                            </div>
                        @empty
                            <div class="ap-hero__rail">
                                <span class="ap-hero__rail-icon">
                                    @svg ('heroicon-o-circle-stack', 'h-5 w-5')
                                </span>
                                <span>
                                    <span class="ap-hero__rail-title">
                                        {{ __('capell-theme-foundation::generic.hero_empty_title') }}
                                    </span>
                                    <span class="ap-hero__rail-copy">
                                        {{ __('capell-theme-foundation::generic.hero_empty_copy') }}
                                    </span>
                                </span>
                                <span
                                    class="ap-hero__rail-status ap-hero__rail-status--blue"
                                >
                                    {{ __('capell-theme-foundation::generic.hero_empty_status') }}
                                </span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-capell-theme-foundation::widget.wrapper>
