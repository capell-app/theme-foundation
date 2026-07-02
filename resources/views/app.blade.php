@php
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Render\RenderHookRegistry;
    use Capell\Frontend\Support\Security\JsonLdScriptSanitizer;
    use Illuminate\Support\Facades\Route;

    $site = Frontend::site();
    $siteMeta = $site?->meta ?? [];
    $metaSchema = data_get($siteMeta, 'meta_schema');
    $customMetaSchema = data_get($siteMeta, 'custom_meta_schema');
    $language = Frontend::language();
    $languageCode = $language?->code ?: app()->getLocale();
    $languageRoot = strtolower(strtok(str_replace('_', '-', (string) $languageCode), '-') ?: (string) $languageCode);
    $textDirection = in_array($languageRoot, ['ar', 'arc', 'ckb', 'dv', 'fa', 'he', 'ks', 'ku', 'ps', 'sd', 'ug', 'ur', 'yi'], true) ? 'rtl' : 'ltr';
    $runtimeManifest ??= null;
    $usesLivewire = $runtimeManifest?->usesLivewire ?? ($livewireEnabled ?? false);
    $beaconRouteName = config('capell-page.frontend.route_name', 'capell-frontend.beacon');
    $usesBeacon = ($runtimeManifest?->usesBeacon ?? false)
        && is_string($beaconRouteName)
        && Route::has($beaconRouteName);

    $bodyClass ??= null;
@endphp

<!DOCTYPE html>
<html
    class="h-full"
    lang="{{ str_replace('_', '-', (string) $languageCode) }}"
    dir="{{ $textDirection }}"
>
    <x-capell::app.head
        :livewire-enabled="$usesLivewire"
        :runtime-manifest="$runtimeManifest"
        :asset-manifest="$assetManifest ?? null"
    />

    <body
        @class([
            'site-app-body',
            'layout-' . $layout->key,
            $layout->getMeta('body_class'),
            $theme->getMeta('body_class'),
            $bodyClass ?? 'min-h-screen min-w-[320px] overflow-x-hidden font-sans leading-normal font-normal text-gray-800 antialiased dark:bg-gray-950 dark:text-gray-100',
        ])
    >
        {{ $slot }}

        {!! app(RenderHookRegistry::class)->renderAll(RenderHookLocation::BodyEnd) !!}

        @if ($usesBeacon)
            <x-capell::page-data />
        @endif

        @stack('scripts')

        @yield('scripts')

        @if ($usesLivewire)
            @livewireScripts
        @endif

        @if ($metaSchema)
            @foreach ($metaSchema as $schema)
                <x-dynamic-component :component="$schema" />
            @endforeach
        @endif

        @if ($customMetaSchema)
            <script type="application/ld+json">
                {!! JsonLdScriptSanitizer::sanitize((string) $customMetaSchema) !!}
            </script>
        @endif
    </body>
</html>
