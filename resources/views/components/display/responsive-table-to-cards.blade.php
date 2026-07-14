@props ([
    'caption' => null,
    'emptyDescription' => null,
    'emptyTitle' => null,
    'headers' => [],
    'rows' => [],
])

{{--
    The desktop table and mobile definition lists expose the same data with
    native semantics. No JavaScript or duplicated interactive controls.
--}}

<div {{ $attributes->class(['responsive-data-table']) }}>
    @if ($headers !== [] && $rows !== [])
        <div
            class="responsive-data-table__scroll"
            tabindex="0"
            role="region"
            @if (filled($caption)) aria-label="{{ $caption }}" @endif
        >
            <table class="responsive-data-table__table">
                @if (filled($caption))
                    <caption>
                        {{ $caption }}
                    </caption>
                @endif

                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th scope="col">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($headers as $cellIndex => $header)
                                <td>{{ data_get($row, $cellIndex, '') }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="responsive-data-table__cards">
            @foreach ($rows as $row)
                <dl class="responsive-data-table__card">
                    @foreach ($headers as $cellIndex => $header)
                        <div class="responsive-data-table__card-row">
                            <dt>{{ $header }}</dt>
                            <dd>{{ data_get($row, $cellIndex, '') }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endforeach
        </div>
    @else
        <x-capell::no-results
            :title="$emptyTitle"
            :description="$emptyDescription"
        />
    @endif
</div>
