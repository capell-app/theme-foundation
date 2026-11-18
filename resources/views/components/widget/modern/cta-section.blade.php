@props([
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'primaryButtonText' => $widget->getMeta('primary_button_text'),
    'primaryButtonUrl' => $widget->getMeta('primary_button_url', '#'),
    'safePrimaryButtonUrl' => '#',
    'secondaryButtonText' => $widget->getMeta('secondary_button_text'),
    'secondaryButtonUrl' => $widget->getMeta('secondary_button_url', '#'),
    'safeSecondaryButtonUrl' => '#',
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])
<x-capell-theme-foundation::widget.wrapper
    class="capell-modern-cta-section widget-ap-cta-section"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="ap-showcase-cta capell-showcase">
        <div class="ap-showcase-cta__panel capell-showcase__inner">
            <div class="capell-showcase__section-head mb-0">
                @if ($title)
                    <h2 class="ap-cta-headline capell-showcase__heading">
                        {{ $title }}
                    </h2>
                @endif

                @if ($content)
                    <p class="ap-cta-description capell-showcase__copy">{{ html_entity_decode(strip_tags((string) $content), ENT_QUOTES | ENT_HTML5) }}</p>
                @endif

                @if ($primaryButtonText || $secondaryButtonText)
                    <div class="flex flex-wrap gap-3 pt-2">
                        @if ($primaryButtonText)
                            <a
                                href="{{ $safePrimaryButtonUrl }}"
                                class="ap-showcase-button ap-showcase-button--primary ap-cta-primary-btn"
                            >
                                <span>{{ $primaryButtonText }}</span>
                                @svg('heroicon-o-arrow-right', 'h-4 w-4')
                            </a>
                        @endif

                        @if ($secondaryButtonText)
                            <a
                                href="{{ $safeSecondaryButtonUrl }}"
                                class="ap-showcase-button ap-showcase-button--secondary ap-cta-secondary-btn"
                            >
                                <span>{{ $secondaryButtonText }}</span>
                                @svg('heroicon-o-code-bracket-square', 'h-4 w-4')
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="ap-showcase-cta__proof">
                <div class="ap-showcase-cta__proof-row">
                    <span class="ap-showcase-cta__proof-mark">
                        @svg('heroicon-o-check', 'h-4 w-4')
                    </span>
                    <p class="ap-showcase-cta__proof-text">{{ __('capell-theme-foundation::demo.cta_proof_content') }}</p>
                </div>

                <div class="ap-showcase-cta__proof-row">
                    <span class="ap-showcase-cta__proof-mark">
                        @svg('heroicon-o-check', 'h-4 w-4')
                    </span>
                    <p class="ap-showcase-cta__proof-text">{{ __('capell-theme-foundation::demo.cta_proof_assets') }}</p>
                </div>

                <div class="ap-showcase-cta__proof-row">
                    <span class="ap-showcase-cta__proof-mark">
                        @svg('heroicon-o-check', 'h-4 w-4')
                    </span>
                    <p class="ap-showcase-cta__proof-text">{{ __('capell-theme-foundation::demo.cta_proof_workflow') }}</p>
                </div>
            </div>
        </div>
    </section>
</x-capell-theme-foundation::widget.wrapper>
