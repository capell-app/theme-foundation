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
                :content-type="$widget->type->content_structure"
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
                    <p class="text-sm font-semibold text-gray-500 uppercase">
                        {{ $family }}
                    </p>
                    <h2>{{ $heading }}</h2>
                    @if (isset($section['summary']))
                        <p>{{ $section['summary'] }}</p>
                    @endif
                </header>

                @if ($isInitialStructuredReference)
                    <p>
                        {{ $notes['Purpose'] ?? 'Use the default Foundation theme contract for this pattern.' }}
                    </p>
                @else
                    <aside aria-label="{{ $heading }} reference notes">
                        <dl class="grid gap-3 md:grid-cols-2">
                            @foreach (['Purpose', 'Layout', 'Content', 'Variant rules', 'Behavior', 'Accessibility'] as $label)
                                <div>
                                    <dt class="font-semibold">{{ $label }}</dt>
                                    <dd>
                                        {{ $notes[$label] ?? 'Use the default Foundation theme contract for this pattern.' }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </aside>
                @endif

                @switch($isInitialStructuredReference ? '__initial_structured_reference' : $key)
                    @case('faq-accordion')
                        <div>
                            <button
                                type="button"
                                aria-expanded="false"
                                aria-controls="faq-panel-demo"
                                id="faq-button-demo"
                            >
                                What does this prove?
                            </button>
                            <div
                                id="faq-panel-demo"
                                role="region"
                                aria-labelledby="faq-button-demo"
                                hidden
                            >
                                <p>
                                    The accordion uses a native button and an
                                    explicitly labelled panel.
                                </p>
                            </div>
                        </div>

                        @break
                    @case('tabs')
                        <div
                            role="tablist"
                            aria-label="Demo tab set"
                        >
                            <button
                                type="button"
                                role="tab"
                                aria-selected="true"
                                aria-controls="tab-panel-overview"
                                id="tab-overview"
                            >
                                Overview
                            </button>
                            <button
                                type="button"
                                role="tab"
                                aria-selected="false"
                                aria-controls="tab-panel-details"
                                id="tab-details"
                                tabindex="-1"
                            >
                                Details
                            </button>
                        </div>
                        <div
                            id="tab-panel-overview"
                            role="tabpanel"
                            aria-labelledby="tab-overview"
                        >
                            <p>
                                Tab panels stay labelled and keyboard reachable.
                            </p>
                        </div>
                        <div
                            id="tab-panel-details"
                            role="tabpanel"
                            aria-labelledby="tab-details"
                            hidden
                        >
                            <p>
                                Arrow-key support belongs in the frontend
                                runtime when enhanced.
                            </p>
                        </div>

                        @break
                    @case('carousel-slider')
                        <section
                            aria-roledescription="carousel"
                            aria-label="Demo carousel"
                        >
                            <button
                                type="button"
                                aria-label="Previous slide"
                            >
                                Previous
                            </button>
                            <article tabindex="0">
                                <h3>Focusable slide</h3>
                                <p>
                                    No autoplay is required for this reference
                                    carousel.
                                </p>
                            </article>
                            <button
                                type="button"
                                aria-label="Next slide"
                            >
                                Next
                            </button>
                        </section>

                        @break
                    @case('table-of-data')
                    @case('complex-table')
                        <table>
                            <caption>{{ $heading }} example</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Pattern</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th scope="row">Semantic HTML</th>
                                    <td>Ready</td>
                                    <td>
                                        Captions and scoped headers are
                                        required.
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        @break
                    @case('form-field-demo')
                    @case('full-form')
                        <form
                            action="#"
                            method="post"
                        >
                            <label for="{{ $key }}-email">Work email</label>
                            <input
                                id="{{ $key }}-email"
                                name="email"
                                type="email"
                                aria-describedby="{{ $key }}-email-help {{ $key }}-email-error"
                            />
                            <p id="{{ $key }}-email-help">
                                Use a visible label and helper text.
                            </p>
                            <p
                                id="{{ $key }}-email-error"
                                role="alert"
                            >
                                Example validation message.
                            </p>
                            <button type="button">Submit demo form</button>
                        </form>

                        @break
                    @case('video-embed')
                        <figure>
                            <video
                                controls
                                aria-label="Demo video embed"
                            ></video>
                            <figcaption>
                                Video embeds include controls and an accessible
                                label.
                            </figcaption>
                        </figure>

                        @break
                    @case('audio-player')
                        <figure>
                            <audio
                                controls
                                aria-label="Demo audio player"
                            ></audio>
                            <figcaption>
                                Audio players include controls and a text
                                equivalent.
                            </figcaption>
                        </figure>

                        @break
                    @case('map-embed')
                    @case('embed-widget')
                        <figure>
                            <iframe
                                title="{{ $heading }} preview"
                                src="about:blank"
                            ></iframe>
                            <figcaption>
                                {{ $heading }} includes a title and nearby text
                                equivalent.
                            </figcaption>
                        </figure>

                        @break
                    @case('heading-hierarchy')
                        <h3>Nested reference heading</h3>
                        <p>
                            Widget internals continue below the page h1 and
                            section h2.
                        </p>

                        @break
                    @case('pre-code-example')
                        <pre><code>&lt;section aria-labelledby="example-heading"&gt;</code></pre>

                        @break
                    @case('unordered-list')
                        <ul>
                            <li>First unordered item</li>
                            <li>Second unordered item</li>
                        </ul>

                        @break
                    @case('ordered-list')
                        <ol>
                            <li>First ordered item</li>
                            <li>Second ordered item</li>
                        </ol>

                        @break
                    @case('definition-list')
                        <dl>
                            <dt>Term</dt>
                            <dd>Definition text.</dd>
                        </dl>

                        @break
                    @default
                        <p>Reference output for {{ $heading }}.</p>
                @endswitch
            </article>
        @endforeach
    </div>
</x-capell-theme-foundation::widget.wrapper>
