@props ([
    'headers' => [],
    'rows' => [],
])

{{--
    Shared Foundation display primitive (Wave 2.7): renders a `<table>` on
    wide viewports and a stacked card-per-row layout below the `sm`
    breakpoint, purely via CSS (Tailwind responsive utility classes) — no
    JS is involved at all. `headers` is a flat list of header labels;
    `rows` is a list of rows, each a list of cell values in the same order
    as `headers`. Token-skinned via `--foundation-*` border/surface
    properties, payload-driven only.
--}}

<div {{ $attributes }}>
    <table class="hidden w-full border-collapse text-left sm:table">
        <thead>
            <tr
                style="border-bottom: 1px solid var(--foundation-border-strong)"
            >
                @foreach ($headers as $header)
                    <th
                        class="px-4 py-3 text-sm font-semibold"
                        style="color: var(--foundation-body-fg)"
                    >
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach ($rows as $row)
                <tr style="border-bottom: 1px solid var(--foundation-border)">
                    @foreach ($row as $cell)
                        <td class="px-4 py-3 text-sm">{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="flex flex-col gap-3 sm:hidden">
        @foreach ($rows as $row)
            <div
                class="rounded-[var(--foundation-radius)] p-4"
                style="
                    border: 1px solid var(--foundation-border);
                    background-color: var(--foundation-card-bg);
                "
            >
                @foreach ($row as $cellIndex => $cell)
                    <div class="flex justify-between gap-4 py-1 text-sm">
                        <span
                            class="font-semibold"
                            style="color: var(--foundation-body-fg)"
                        >
                            {{ $headers[$cellIndex] ?? '' }}
                        </span>
                        <span class="text-right">{{ $cell }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
