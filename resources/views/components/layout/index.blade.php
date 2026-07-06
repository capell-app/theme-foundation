@props ([
    'containerClass' => null,
    'footer' => null,
    'header' => null,
    'mainClass' => null,
    'mainContainerClass' => null,
    'pageSlot' => null,
])
@php
    use Capell\Core\Contracts\Pageable;
    use Capell\Core\Enums\ContentStructure;
    use Capell\Core\Models\Site;
    use Capell\Frontend\Facades\Frontend;
    use Illuminate\Database\Eloquent\Model;
    use Livewire\Blaze\Blaze;

    $theme ??= Frontend::theme();
    $page ??= Frontend::page();
    $layout ??= Frontend::layout();
    $site ??= Frontend::site();
    $layoutMeta = is_array($layout?->meta ?? null) ? $layout->meta : [];
    $header ??= array_key_exists('header', $layoutMeta) ? $layoutMeta['header'] : null;
    $footer ??= array_key_exists('footer', $layoutMeta) ? $layoutMeta['footer'] : null;
    $isSystemPageLayout ??= data_get($layout->admin ?? [], 'system_page_layout') === true;
    $siteRelations = $site instanceof Site ? $site->getRelations() : [];
    $pageRelations = $page instanceof Model ? $page->getRelations() : [];
    $siteDefaultDomain = $siteRelations['defaultDomain'] ?? null;
    $siteDomain = $siteRelations['siteDomain'] ?? null;
    $siteHomeUrl = data_get($siteDefaultDomain, 'url') ?? data_get($siteDomain, 'url') ?? '/';
    $siteLogoBladeView = $site instanceof Site ? $site->getMeta('logo_blade_view', 'brand.capell-logo') : 'brand.capell-logo';
    $siteLogoBladeView = is_string($siteLogoBladeView) && view()->exists($siteLogoBladeView)
        ? $siteLogoBladeView
        : null;
    $siteLogo = $siteRelations['logo'] ?? null;
    $siteTranslation = $siteRelations['translation'] ?? null;
    $pageTranslation = $pageRelations['translation'] ?? null;
    $pageType = $pageRelations['type'] ?? null;
    $htmlContentStructure = ContentStructure::Html;
    $isThemeDemoContactPage = data_get($page?->meta ?? [], 'theme_demo.surface') === 'contact'
        && view()->exists('capell-theme-foundation::components.demo.contact-page');
    $layoutNeighborLinks ??= null;
@endphp

@if ($isSystemPageLayout && $isThemeDemoContactPage)
    <div
        {{ $attributes->merge(['style' => 'min-height: 100vh; background: #faf8ff; color: #131b2e;']) }}
    >
        <main id="main">
            @include ('capell-theme-foundation::components.demo.contact-page', [
                'page' => $page,
                'site' => $site,
            ])

            {{ $pageSlot ?? $slot }}
        </main>
    </div>
@elseif ($isSystemPageLayout)
    <div
        {{ $attributes->merge(['style' => 'min-height: 100vh; display: flex; flex-direction: column; background: #f8fafc; color: #0f172a;']) }}
    >
        <main
            id="main"
            style="
                box-sizing: border-box;
                width: 100%;
                max-width: 48rem;
                min-height: 100vh;
                margin: 0 auto;
                padding: 3rem 1.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
            "
        >
            <a
                href="{{ $siteHomeUrl }}"
                style="
                    margin-bottom: 2.5rem;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    color: #0f172a;
                    font-size: 1.125rem;
                    font-weight: 600;
                    text-decoration: none;
                "
            >
                @if ($siteLogoBladeView)
                    @include ($siteLogoBladeView, ['class' => 'h-10 w-auto'])
                @elseif ($siteLogo)
                    <x-capell::logo :media="$siteLogo" />
                @else
                    <span>{{ $siteTranslation?->title ?? $site?->name }}</span>
                @endif
            </a>

            <x-capell::content
                :content="$pageTranslation?->content ?? ''"
                :content-type="$pageType?->content_structure ?? $htmlContentStructure"
                :title="$pageTranslation?->title ?? ''"
                class="[&_h1]:text-slate-950 mx-auto max-w-2xl text-slate-700"
                heading-tag="h1"
                heading-size="h1"
                text-align="center"
            />

            {{ $pageSlot ?? $slot }}
        </main>
    </div>
@else
    <div
        {{ $attributes->merge(['class' => 'flex min-h-screen flex-col bg-white dark:bg-gray-900']) }}
    >
        <a
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:text-gray-900 focus:shadow"
            href="#main"
        >
            {{ __('capell-frontend::generic.skip_link') }}
        </a>

        @if ($header)
            {{ $header }}
        @elseif ($header === null && (! isset($theme['meta']['header']) || $theme['meta']['header'] !== false))
            @if (! empty($theme['meta']['header_file']))
                <x-dynamic-component
                    :component="$theme['meta']['header_file']"
                />
            @else
                <x-capell::header.index />
            @endif
        @endif

        {{--
            Blaze's static component-tag optimization compiles the sibling
            tags in this view (header, content, logo) into inlined function
            calls sharing Blaze's own output-buffering bookkeeping. When
            `<x-capell::layout.main>` — a class-backed component that renders
            layout-builder's dynamic, render-hook-driven main content — is
            invoked immediately after those Blaze-compiled calls in the same
            buffer scope, Laravel's native component stack (`$__env`) desyncs
            and `capell::layout.main` silently renders empty output instead
            of the page's real widgets. Disabling Blaze around this one
            invocation mirrors the same defence
            `capell-layout-builder::components.layout.widget` and
            `Capell\LayoutBuilder\Support\RenderHooks\RegisterMainContentLayoutHook`
            already use around their own dynamic, hook-driven renders.
        --}}
        @php
            $wasBlazeEnabledForMain = Blaze::isEnabled();
            Blaze::disable();
        @endphp
        <x-capell::layout.main
            :$layout
            :$page
            :$theme
            :layout-neighbor-links="$layoutNeighborLinks"
            :page-slot="$pageSlot ?? $slot"
            :container-class="$containerClass"
            :main-class="$mainClass"
            :main-container-class="$mainContainerClass"
        />
        @php
            if ($wasBlazeEnabledForMain) {
                Blaze::enable();
            }
        @endphp

        {{--
            Same Blaze static-tag desync as `<x-capell::layout.main>` above:
            re-enabling Blaze immediately before the footer leaves it
            rendering in the same corrupted `$__env` buffer scope
            `<x-capell::layout.main>`'s own comment describes, so the footer
            (including its plain, literal `<x-capell::footer.index />` tag
            path -- this isn't specific to `<x-dynamic-component>`) silently
            renders empty. Disabling Blaze around the footer too, the same
            way it's disabled around `layout.main`, fixes it.
        --}}
        @php
            $wasBlazeEnabledForFooter = Blaze::isEnabled();
            Blaze::disable();
        @endphp
        @if ($footer)
            {{ $footer }}
        @elseif ($footer !== false && (! isset($theme['meta']['footer']) || $theme['meta']['footer'] !== false))
            @if (($theme['meta']['footer_file'] ?? 'capell::footer') === 'capell::footer')
                <x-capell::footer.index />
            @else
                <x-dynamic-component
                    :component="$theme['meta']['footer_file']"
                />
            @endif
        @endif
        @php
            if ($wasBlazeEnabledForFooter) {
                Blaze::enable();
            }
        @endphp
    </div>
@endif
