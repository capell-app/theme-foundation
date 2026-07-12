@props ([
    'site',
    'contactPage' => null,
])

@php
    $businessName = $site->getMeta('business_name');
    $email = $site->getMeta('email');
    $phone = $site->getMeta('phone');
    $siteRelations = method_exists($site, 'getRelations') ? $site->getRelations() : [];
    $siteTranslation = $siteRelations['translation'] ?? null;
    $siteTitle = data_get($siteTranslation, 'title', $site->name);
    $tagline = data_get($siteTranslation, 'meta.tagline');
@endphp

<div {{ $attributes->merge(['class' => 'footer-site-info space-y-4']) }}>
    <a
        href="{{ $site->siteDomain->url }}"
        class="text-brand hover:text-primary focus:text-primary mb-3 inline-block"
    >
        @if ($site->logo || $site->logoInverted)
            @if ($site->logoInverted)
                <x-capell::logo
                    :media="$site->logoInverted"
                    :class="'footer-logo object-top-left max-h-[32vh] object-contain' . ($site->logo ? ' hidden dark:widget' : '')"
                />
            @endif

            @if ($site->logo)
                <x-capell::logo
                    :media="$site->logo"
                    :class="'footer-logo object-top-left max-h-[32vh] object-contain' . ($site->logoInverted ? ' dark:hidden' : '')"
                />
            @endif
        @else
            <span class="footer-logo-text text-2xl leading-tight font-semibold">
                {{ $siteTitle }}
            </span>
        @endif
    </a>

    @if ($tagline)
        <p
            class="footer-tagline max-w-prose text-sm leading-6 text-[var(--color-footer-muted)]"
        >
            {{ $tagline }}
        </p>
    @endif

    @if ($businessName || $email || $phone || $contactPage?->pageUrl)
        <address
            class="footer-contact text-sm leading-6 text-[var(--color-footer-muted)] not-italic"
        >
            @if ($businessName)
                <div>{{ $businessName }}</div>
            @endif

            @if ($email)
                <div>
                    <a
                        href="mailto:{{ $email }}"
                        class="hover:text-primary focus:text-primary"
                    >
                        {{ $email }}
                    </a>
                </div>
            @endif

            @if ($phone)
                <div>
                    <a
                        href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}"
                        class="hover:text-primary focus:text-primary"
                    >
                        {{ $phone }}
                    </a>
                </div>
            @endif

            @if ($contactPage?->pageUrl)
                <div>
                    <a
                        href="{{ $contactPage->pageUrl->full_url }}"
                        class="hover:text-primary focus:text-primary font-medium text-[var(--color-footer-link)]"
                        wire:navigate
                    >
                        {{ $contactPage->getTranslation('label') ?? __('capell-theme-foundation::generic.contact') }}
                    </a>
                </div>
            @endif
        </address>
    @endif

    @if ($socialLinks = $site->getMeta('social_links'))
        <x-capell::footer.social-links :links="$socialLinks" />
    @endif
</div>
