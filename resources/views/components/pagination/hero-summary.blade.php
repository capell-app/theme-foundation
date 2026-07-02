@props([
    'results' => '',
    'resultsFoundText' => __('capell-frontend::messages.results_found'),
])

@php
    if (! $results || (method_exists($results, 'total') ? $results->total() === 0 : $results->isEmpty())) {
        return;
    }

    $isPaginated = $results->perPage() < $results->total();
    $from = ($results->currentPage() - 1) * $results->perPage() + 1;
    $to = ($results->currentPage() - 1) * $results->perPage() + count($results->items());
@endphp

<div
    {{
        $attributes
            ->merge([
                'aria-label' => __('capell-frontend::messages.pagination_info', [
                    'from' => $from,
                    'to' => $to,
                    'total' => $results->total(),
                ]),
            ])
            ->class('capell-pagination-hero-summary pagination-info tracking-loose text-sm leading-none font-normal text-gray-500 dark:text-gray-400')
    }}
>
    @if ($isPaginated)
        {{ __('capell-frontend::messages.showing') }}
        <span
            class="pagination-range font-semibold tracking-normal dark:text-white"
        >
            {{ $from }} to {{ $to }}
        </span>
        {{ __('capell-frontend::messages.of') }}
    @else
        {{ __('capell-frontend::messages.showing') }}
    @endif

    <span
        class="pagination-total font-semibold tracking-normal dark:text-white"
    >
        {{ $results->total() }}
    </span>
    {{ $resultsFoundText }}
</div>
