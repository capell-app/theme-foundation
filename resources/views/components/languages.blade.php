<?php
use Capell\Frontend\Facades\Frontend;

$page = Frontend::page();
$language = Frontend::language();
$site = Frontend::site();
$theme = Frontend::theme();

?>

@props([
    'linkClass' => 'dropdown-item hover:text-primary focus:text-primary widget w-full bg-transparent px-4 py-3 text-left text-base text-sm leading-none font-medium whitespace-nowrap',
    'name' => 'languages',
    'dropdownLabelClass' => '',
    'darkMode' => false,
    'align' => 'left',
    'theme' => 'light',
    'languages',
])
<x-capell::dropdown
    name="{{ $name }}"
    :attributes="$attributes->class(['capell-languages', 'inline-flex'])"
    :label="__('capell-frontend::generic.languages')"
    :dark-mode="false"
    :placement="$align === 'right' ? 'top-end' : 'top-start'"
    dropdown-class="z-50 min-w-56 overflow-hidden border border-gray-200/80 shadow-lg shadow-slate-900/10 focus:outline-hidden"
>
    <x-slot:trigger
        @class([
            'hover:text-primary focus:text-primary focus:ring-primary/50 dark:focus:ring-primary cursor-pointer gap-2 rounded-md border border-white/10 px-4 py-1.5 focus:outline-none focus:ring-2',
            'dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' => $darkMode && $theme->withDarkMode,
        ])
    >
        <img
            class="h-4 w-4"
            src="{{ asset("vendor/blade-country-flags/4x3-{$language->flag}.svg") }}"
            alt=""
            width="16"
            height="16"
            loading="lazy"
        />
        <span class="{{ $dropdownLabelClass }}">
            <span>{{ $language->name }}</span>
            @svg('heroicon-m-chevron-down', 'inline h-4 w-4')
        </span>
    </x-slot>

    <div
        class="border-b border-gray-200 px-4 py-3 text-xs font-semibold tracking-wide text-gray-500"
    >
        {{ __('capell-theme-foundation::generic.change_language') }}
    </div>

    @foreach ($languages as $siteLanguage)
        @continue($siteLanguage['id'] === $language->id)
        <div>
            <a
                href="{{ $siteLanguage['url'] }}"
                class="{{ $linkClass }}"
                @wireNavigate
            >
                <img
                    class="mr-2 inline-block h-4 w-4 align-top"
                    src="{{ asset("vendor/blade-country-flags/4x3-{$siteLanguage['flag']}.svg") }}"
                    alt=""
                    width="16"
                    height="16"
                    loading="lazy"
                />
                {{ $siteLanguage['name'] }}
            </a>
        </div>
    @endforeach
</x-capell::dropdown>
