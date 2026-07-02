<div
    {{
        $attributes->class([
            'actions flex max-w-full min-w-0 flex-wrap gap-2 lg:gap-x-4',
            'justify-center' => $align === 'center',
            'justify-start' => $align === 'start' || $align === 'left',
            'justify-end' => $align === 'end' || $align === 'right',
        ])
    }}
>
    {{ $slot }}
    @foreach ($resolvedActions as $action)
        @if (($action['kind'] ?? null) === 'public_action')
            <form
                method="post"
                action="{{ route('capell-public-actions.submit', ['action' => $action['public_action_key']]) }}"
                class="inline-flex"
            >
                @csrf
                @foreach (($action['payload'] ?? []) as $payloadKey => $payloadValue)
                    @if (is_string($payloadKey) && is_scalar($payloadValue))
                        <input
                            type="hidden"
                            name="{{ $payloadKey }}"
                            value="{{ (string) $payloadValue }}"
                        />
                    @endif
                @endforeach

                <button
                    type="submit"
                    class="{{ 'action-item max-w-full rounded-full px-3.5 py-2 text-xs font-semibold whitespace-normal transition sm:px-5 sm:py-3 sm:text-sm ' . (($action['color'] ?? $buttonColor) === 'secondary' ? 'border border-slate-300 text-slate-800 hover:border-slate-950 dark:border-white/15 dark:text-slate-200 dark:hover:border-white' : 'bg-[var(--theme-accent)] text-slate-950 hover:bg-white') . ' ' . ($actionItemClass ?? '') }}"
                >
                    {{ $action['label'] }}
                </button>
            </form>
            @continue
        @endif

        @if (($action['kind'] ?? null) === 'video_popup')
            <x-capell::button
                url="#"
                :color="$action['color'] ?? $buttonColor"
                :icon="$action['icon'] ?? 'heroicon-o-play-circle'"
                :outline="$buttonOutline === false"
                :size="$buttonSize"
                :weight="$buttonWeight"
                :wire-navigation="false"
                :class="'lightbox action-item max-w-full whitespace-normal' . ' ' . ($actionItemClass ?? '')"
                :data-lightbox="$action['video_url']"
                data-type="video"
                :data-title="$action['label'] ?? ''"
                :data-group="$action['group'] ?? 'action-videos'"
            >
                @if ($action['hide_label'] ?? false)
                    <span class="sr-only">
                        {{ $action['label'] }}
                    </span>
                @else
                    {{ $action['label'] }}
                @endif
            </x-capell::button>
            @continue
        @endif

        <x-capell::button
            :url="$action['url']"
            :target="$action['target'] ?? ''"
            :color="$action['color'] ?? $buttonColor"
            :icon="$action['icon'] ?? ''"
            :outline="$buttonOutline === false"
            :size="$buttonSize"
            :weight="$buttonWeight"
            :wire-navigation="$action['wire_navigation'] ?? false"
            :class="'action-item max-w-full whitespace-normal' . ' ' . ($actionItemClass ?? '')"
        >
            @if ($action['hide_label'] ?? false)
                <span class="sr-only">
                    {{ $action['label'] }}
                </span>
            @else
                {{ $action['label'] }}
            @endif
        </x-capell::button>
    @endforeach
</div>
