@props ([
    'assetRenderDataItems',
    'currency' => $widget->getMeta('currency', '$'),
    'billingOptions' => $widget->getMeta('billing_options', 'monthly'),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-theme-foundation::widget.wrapper
    class="capell-modern-pricing-table widget-ap-pricing-table"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section class="px-6 py-12 md:px-12 md:py-16">
        @if ($widget->translation)
            {{-- prettier-ignore --}}
            <div class="mx-auto mb-12 max-w-2xl text-center">
                @if ($widget->translation->title)
                    <h2
                        class="text-3xl font-bold tracking-tight text-gray-900 md:text-4xl"
                    >
                        {{ $widget->translation->title }}
                    </h2>
                @endif

                @if ($widget->translation->content)
                    {{-- prettier-ignore --}}
                    <p class="mt-3 text-lg text-gray-500">
                        {{ strip_tags($widget->translation->content) }}
                    </p>
                @endif
            </div>
        @endif

        @if ($billingOptions === 'both')
            <div class="mb-12 flex items-center justify-center gap-4">
                <span class="text-gray-700">
                    {{ __('capell-theme-foundation::generic.pricing_monthly') }}
                </span>
                <button
                    class="billing-toggle-button relative h-8 w-14 rounded-full bg-stone-800 transition-colors"
                    data-billing-toggle
                >
                    <div
                        class="billing-toggle-dot absolute top-1 left-1 h-6 w-6 rounded-full bg-white transition-all"
                    ></div>
                </button>
                <span class="text-gray-700">
                    {{ __('capell-theme-foundation::generic.pricing_annual') }}
                </span>
                <span class="text-sm font-semibold text-emerald-700">
                    {{ __('capell-theme-foundation::generic.pricing_savings_badge') }}
                </span>
            </div>
        @endif

        <div
            class="pricing-grid mx-auto grid max-w-5xl grid-cols-1 gap-6 md:grid-cols-3"
            data-billing="{{ $billingOptions === 'both' ? 'monthly' : $billingOptions }}"
        >
            @forelse ($assetRenderDataItems as $assetRenderDataItem)
                @php
                    $assetRenderData = $assetRenderDataItem['renderData'];
                    $asset = $assetRenderData->asset;
                    $price = $asset?->getMeta('price', '0') ?? '0';
                    $priceAnnual = $asset?->getMeta('price_annual', $price) ?? $price;
                    $featured = (bool) ($asset?->getMeta('featured', false) ?? false);
                    $ctaLabel = $asset?->getMeta('cta_label', 'Get Started') ?? 'Get Started';
                    $ctaUrl = $asset?->getMeta('cta_url', '#') ?? '#';
                    $features = $asset?->getMeta('features', []) ?? [];
                @endphp

                @if ($featured)
                    <div
                        class="pricing-plan relative rounded-xl bg-stone-900 p-8 text-white shadow-xl md:-my-4"
                        data-price-monthly="{{ $price }}"
                        data-price-annual="{{ $priceAnnual }}"
                    >
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span
                                class="rounded-full bg-amber-400 px-4 py-1 text-xs font-bold text-amber-900"
                            >
                                {{ __('capell-theme-foundation::generic.pricing_featured_badge') }}
                            </span>
                        </div>

                        @if ($assetRenderData->title)
                            <h3
                                class="mb-1 text-2xl font-bold tracking-tight text-white"
                            >
                                {{ $assetRenderData->title }}
                            </h3>
                        @endif

                        @if ($assetRenderData->content)
                            <p class="mb-6 text-sm text-stone-400">
                                {{ strip_tags($assetRenderData->content) }}
                            </p>
                        @endif

                        <div class="price-container mb-6">
                            <span
                                class="plan-price text-4xl font-bold text-white"
                            >
                                {{ $price !== 'Custom' ? $currency : '' }}{{ $price }}
                            </span>
                            @if ($price !== 'Custom')
                                <span class="billing-period text-stone-400">
                                    /month
                                </span>
                            @endif
                        </div>

                        @if (count($features) > 0)
                            <ul class="mb-8 space-y-3">
                                @foreach ($features as $feature)
                                    <li
                                        class="flex items-center gap-2 text-stone-200"
                                    >
                                        <span
                                            class="font-bold text-emerald-400"
                                        >
                                            ✓
                                        </span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <a
                            href="{{ $ctaUrl }}"
                            class="widget w-full rounded-lg bg-white px-6 py-3 text-center font-semibold text-stone-900 transition-opacity hover:opacity-90"
                        >
                            {{ $ctaLabel }}
                        </a>
                    </div>
                @else
                    <div
                        class="pricing-plan relative rounded-xl border border-stone-200 bg-white p-8"
                        data-price-monthly="{{ $price }}"
                        data-price-annual="{{ $priceAnnual }}"
                    >
                        @if ($assetRenderData->title)
                            <h3
                                class="mb-1 text-2xl font-bold tracking-tight text-gray-900"
                            >
                                {{ $assetRenderData->title }}
                            </h3>
                        @endif

                        @if ($assetRenderData->content)
                            <p class="mb-6 text-sm text-gray-500">
                                {{ strip_tags($assetRenderData->content) }}
                            </p>
                        @endif

                        <div class="price-container mb-6">
                            <span
                                class="plan-price text-4xl font-bold text-gray-900"
                            >
                                {{ $price !== 'Custom' ? $currency : '' }}{{ $price }}
                            </span>
                            @if ($price !== 'Custom')
                                <span class="billing-period text-gray-500">
                                    /month
                                </span>
                            @endif
                        </div>

                        @if (count($features) > 0)
                            <ul class="mb-8 space-y-3">
                                @foreach ($features as $feature)
                                    <li
                                        class="flex items-center gap-2 text-gray-700"
                                    >
                                        <span
                                            class="font-bold text-emerald-600"
                                        >
                                            ✓
                                        </span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <a
                            href="{{ $ctaUrl }}"
                            class="widget w-full rounded-lg border border-stone-200 bg-white px-6 py-3 text-center font-semibold text-gray-700 transition-colors hover:border-emerald-300 hover:text-emerald-700"
                        >
                            {{ $ctaLabel }}
                        </a>
                    </div>
                @endif
            @empty
                <div class="col-span-full py-12 text-center">
                    <p class="text-gray-500">
                        {{ __('capell-theme-foundation::generic.empty_pricing_plans') }}
                    </p>
                </div>
            @endforelse
        </div>
    </section>
</x-capell-theme-foundation::widget.wrapper>
