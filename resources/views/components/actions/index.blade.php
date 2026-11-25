{{-- @blaze-standard-compiler: class-backed actions need their resolved component data. --}}
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
                    class="{{ 'action-item max-w-full rounded-full px-3.5 py-2 text-xs font-semibold whitespace-normal transition sm:px-5 sm:py-3 sm:text-sm ' . (($action['color'] ?? $buttonColor) === 'secondary' ? 'border border-[var(--foundation-border)] text-[var(--foundation-body-fg)] hover:border-[var(--foundation-heading-fg)]' : 'bg-[var(--theme-accent)] text-[var(--foundation-inverse-bg)] hover:bg-white') . ' ' . ($actionItemClass ?? '') }}"
                >
                    {{ $action['label'] }}
                </button>
            </form>
            @continue
        @endif

        @if (($action['kind'] ?? null) === 'video_popup')
            {{-- The trigger links to the film itself, so it still opens when the lightbox script has not loaded; an unsafe URL renders no trigger. --}}
            @continue(\Capell\Core\Support\Security\PublicUrlSanitizer::sanitize($action['video_url'] ?? null) === null)

            <x-capell::button
                :url="\Capell\Core\Support\Security\PublicUrlSanitizer::sanitize($action['video_url'])"
                :color="$action['color'] ?? $buttonColor"
                :icon="$action['icon'] ?? 'heroicon-o-play-circle'"
                :outline="$buttonOutline === false"
                :size="$buttonSize"
                :weight="$buttonWeight"
                :wire-navigation="false"
                :class="'lightbox action-item max-w-full whitespace-normal' . ' ' . ($actionItemClass ?? '')"
                :data-lightbox="\Capell\Core\Support\Security\PublicUrlSanitizer::sanitize($action['video_url'])"
                data-type="video"
                :data-title="$action['label'] ?? ''"
                :data-group="$action['group'] ?? 'action-videos'"
            >
                @if ($action['hide_label'] ?? false)
                    <span class="sr-only"> {{ $action['label'] }} </span>
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
                <span class="sr-only"> {{ $action['label'] }} </span>
            @else
                {{ $action['label'] }}
            @endif
        </x-capell::button>
    @endforeach
</div>
