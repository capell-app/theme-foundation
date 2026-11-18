@php
    $sections = $widget->getMeta('sections', []);
    $family = (string) $widget->getMeta('family', 'reference');
    $isInitialStructuredReference = $widget->key === 'kitchen-sink-structured-text';
@endphp

<x-capell-theme-foundation::widget.wrapper
    class="capell-kitchen-sink-reference"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <div class="space-y-8">
        @if ($widget->translation)
            <x-capell::content
                :compact="true"
                :content="$widget->translation->content"
                :content-type="$widget->blueprint->content_structure"
                :block-media-owner="$widget"
                :title="$widget->translation->title"
            />
        @endif

        @foreach ($sections as $section)
            @php
                $key = (string) ($section['key'] ?? 'section-' . $loop->iteration);
                $heading = (string) ($section['heading'] ?? str($key)->headline());
                $notes = $section['notes'] ?? [];
            @endphp

            <article
                id="{{ $key }}"
                class="space-y-4 border-t border-gray-200 pt-6"
            >
                <header class="space-y-2">
                    <p class="text-sm font-semibold text-gray-500 uppercase">{{ $family }}</p>
                    <h2>{{ $heading }}</h2>
                    @if (isset($section['summary']))
                        <p>{{ $section['summary'] }}</p>
                    @endif
                </header>

                @if ($isInitialStructuredReference)
                    <p>{{ $notes['Purpose'] ?? __('capell-theme-foundation::demo.kitchen_sink_default_note') }}</p>
                @else
                    <aside aria-label="{{ $heading }} reference notes">
                        <dl class="grid gap-3 md:grid-cols-2">
                            @foreach ([
                                'Purpose' => __('capell-theme-foundation::demo.kitchen_sink_label_purpose'),
                                'Layout' => __('capell-theme-foundation::demo.kitchen_sink_label_layout'),
                                'Content' => __('capell-theme-foundation::demo.kitchen_sink_label_content'),
                                'Variant rules' => __('capell-theme-foundation::demo.kitchen_sink_label_variant_rules'),
                                'Behavior' => __('capell-theme-foundation::demo.kitchen_sink_label_behavior'),
                                'Accessibility' => __('capell-theme-foundation::demo.kitchen_sink_label_accessibility'),
                            ] as $noteKey => $label)
                                <div>
                                    <dt class="font-semibold">{{ $label }}</dt>
                                    <dd>
                                        {{ $notes[$noteKey] ?? __('capell-theme-foundation::demo.kitchen_sink_default_note') }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </aside>
                @endif

                @switch ($isInitialStructuredReference ? '__initial_structured_reference' : $key)
                    @case ('faq-accordion')
                        <div>
                            <button
                                type="button"
                                aria-expanded="false"
                                aria-controls="faq-panel-demo"
                                id="faq-button-demo"
                            >
                                {{ __('capell-theme-foundation::demo.kitchen_sink_faq_button') }}
                            </button>
                            <div
                                id="faq-panel-demo"
                                role="region"
                                aria-labelledby="faq-button-demo"
                                hidden
                            >
                                <p>{{ __('capell-theme-foundation::demo.kitchen_sink_faq_panel') }}</p>
                            </div>
                        </div>
                        @break
                    @case ('tabs')
                        <div
                            role="tablist"
                            aria-label="{{ __('capell-theme-foundation::generic.demo_tab_set') }}"
                        >
                            <button
                                type="button"
                                role="tab"
                                aria-selected="true"
                                aria-controls="tab-panel-overview"
                                id="tab-overview"
                            >
                                {{ __('capell-theme-foundation::demo.kitchen_sink_tab_overview') }}
                            </button>
                            <button
                                type="button"
                                role="tab"
                                aria-selected="false"
                                aria-controls="tab-panel-details"
                                id="tab-details"
                                tabindex="-1"
                            >
                                {{ __('capell-theme-foundation::demo.kitchen_sink_tab_details') }}
                            </button>
                        </div>
                        <div
                            id="tab-panel-overview"
                            role="tabpanel"
                            aria-labelledby="tab-overview"
                        >
                            <p>{{ __('capell-theme-foundation::demo.kitchen_sink_tab_overview_panel') }}</p>
                        </div>
                        <div
                            id="tab-panel-details"
                            role="tabpanel"
                            aria-labelledby="tab-details"
                            hidden
                        >
                            <p>{{ __('capell-theme-foundation::demo.kitchen_sink_tab_details_panel') }}</p>
                        </div>
                        @break
                    @case ('carousel-slider')
                        <section
                            aria-roledescription="carousel"
                            aria-label="{{ __('capell-theme-foundation::generic.demo_carousel') }}"
                        >
                            <button
                                type="button"
                                aria-label="{{ __('capell-theme-foundation::generic.previous_slide') }}"
                            >
                                {{ __('capell-theme-foundation::demo.kitchen_sink_carousel_previous') }}
                            </button>
                            <article tabindex="0">
                                <h3>
                                    {{ __('capell-theme-foundation::demo.kitchen_sink_carousel_slide_title') }}
                                </h3>
                                <p>{{ __('capell-theme-foundation::demo.kitchen_sink_carousel_slide_copy') }}</p>
                            </article>
                            <button
                                type="button"
                                aria-label="{{ __('capell-theme-foundation::generic.next_slide') }}"
                            >
                                {{ __('capell-theme-foundation::demo.kitchen_sink_carousel_next') }}
                            </button>
                        </section>
                        @break
                    @case ('table-of-data')
                    @case ('complex-table')
                        <table>
                            <caption>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_table_caption', ['heading' => $heading]) }}
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col">
                                        {{ __('capell-theme-foundation::demo.kitchen_sink_table_pattern') }}
                                    </th>
                                    <th scope="col">
                                        {{ __('capell-theme-foundation::demo.kitchen_sink_table_status') }}
                                    </th>
                                    <th scope="col">
                                        {{ __('capell-theme-foundation::demo.kitchen_sink_table_notes') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        {{ __('capell-theme-foundation::demo.kitchen_sink_table_row_pattern') }}
                                    </th>
                                    <td>
                                        {{ __('capell-theme-foundation::demo.kitchen_sink_table_row_status') }}
                                    </td>
                                    <td>
                                        {{ __('capell-theme-foundation::demo.kitchen_sink_table_row_notes') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        @break
                    @case ('form-field-demo')
                    @case ('full-form')
                        <x-capell::form-embed
                            handle="foundation-enquiry"
                            :instance-id="$key . '-form'"
                        />
                        @break
                    @case ('video-embed')
                        <figure>
                            <video
                                controls
                                aria-label="{{ __('capell-theme-foundation::generic.demo_video_embed') }}"
                            ></video>
                            <figcaption>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_video_caption') }}
                            </figcaption>
                        </figure>
                        @break
                    @case ('audio-player')
                        <figure>
                            <audio
                                controls
                                aria-label="{{ __('capell-theme-foundation::generic.demo_audio_player') }}"
                            ></audio>
                            <figcaption>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_audio_caption') }}
                            </figcaption>
                        </figure>
                        @break
                    @case ('map-embed')
                    @case ('embed-widget')
                        <figure>
                            <iframe
                                title="{{ $heading }} preview"
                                src="about:blank"
                            ></iframe>
                            <figcaption>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_embed_caption', ['heading' => $heading]) }}
                            </figcaption>
                        </figure>
                        @break
                    @case ('heading-hierarchy')
                        <h3>
                            {{ __('capell-theme-foundation::demo.kitchen_sink_heading_title') }}
                        </h3>
                        <p>{{ __('capell-theme-foundation::demo.kitchen_sink_heading_copy') }}</p>
                        @break
                    @case ('pre-code-example')
                        <pre><code>&lt;section aria-labelledby="example-heading"&gt;</code></pre>
                        @break
                    @case ('unordered-list')
                        <ul>
                            <li>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_ul_first') }}
                            </li>
                            <li>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_ul_second') }}
                            </li>
                        </ul>
                        @break
                    @case ('ordered-list')
                        <ol>
                            <li>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_ol_first') }}
                            </li>
                            <li>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_ol_second') }}
                            </li>
                        </ol>
                        @break
                    @case ('definition-list')
                        <dl>
                            <dt>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_term') }}
                            </dt>
                            <dd>
                                {{ __('capell-theme-foundation::demo.kitchen_sink_definition') }}
                            </dd>
                        </dl>
                        @break
                    @default
                        <p>{{ __('capell-theme-foundation::demo.kitchen_sink_default_output', ['heading' => $heading]) }}</p>
                @endswitch
            </article>
        @endforeach
    </div>
</x-capell-theme-foundation::widget.wrapper>
