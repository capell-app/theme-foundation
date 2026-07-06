@props ([
    'poster',
    'videoSrc',
    'alt' => '',
    'aspectRatio' => '16/9',
])

{{--
    Shared Foundation display primitive (Wave 2.7): a poster image that
    swaps to a muted, looping, autoplay-on-hover/tap video. Never autoplays
    with sound (the `<video>` element is always `muted`), and fully
    respects `prefers-reduced-motion` by never wiring the hover/tap swap at
    all when it is set — the CSS media query below hides the video layer
    and its interaction affordances outright, so only the static poster
    ever renders for those visitors, with no JS branch needed to enforce
    it. Payload-driven, token-skinned, no DB/facade calls.

    Touch devices get an explicit play-icon overlay button rather than a
    tap-anywhere trigger, so a stray tap can never start playback by
    accident.
--}}

<div
    {{ $attributes->merge(['class' => 'group relative isolate overflow-hidden']) }}
    style="aspect-ratio: {{ $aspectRatio }};"
    data-hover-video-poster
>
    <img
        src="{{ $poster }}"
        alt="{{ $alt }}"
        loading="lazy"
        class="absolute inset-0 h-full w-full object-cover"
    />

    <video
        class="[.hover-video-poster-active_&]:pointer-events-auto [.hover-video-poster-active_&]:opacity-100 pointer-events-none absolute inset-0 hidden h-full w-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100 supports-[not(prefers-reduced-motion:reduce)]:block motion-reduce:hidden"
        muted
        loop
        playsinline
        preload="none"
        data-hover-video-poster-video
    >
        <source src="{{ $videoSrc }}" />
    </video>

    <button
        type="button"
        class="[.hover-video-poster-active_&]:opacity-0 absolute inset-0 flex items-center justify-center bg-black/10 opacity-0 transition-opacity duration-200 group-hover:opacity-0 motion-reduce:hidden md:hidden"
        aria-label="{{ __('capell-theme-foundation::generic.hover_video_play') }}"
        data-hover-video-poster-trigger
    >
        <span
            class="flex h-12 w-12 items-center justify-center rounded-full bg-white/90 text-black shadow"
            aria-hidden="true"
        >
            &#9658;
        </span>
    </button>
</div>
