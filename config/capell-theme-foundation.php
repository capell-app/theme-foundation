<?php

declare(strict_types=1);

return [
    // Asset build tool: 'vite' | 'mix' | 'static'
    'asset_build_tool' => 'vite',

    // NPM dependencies required by the default theme
    'npm_dependencies' => [
        '@tailwindcss/typography' => '^0.5.9',
        '@tailwindcss/vite' => '^0.0.13',
        '@awcodes/alpine-floating-ui' => '^3.5.0',
        '@ryangjchandler/alpine-tooltip' => '^2.0.1',
        'autoprefixer' => '^10.4.13',
        'swiper' => '^11.1.14',
        'fontaine' => '^0.5.0',
        'laravel-vite-plugin' => '^3.1.0',
        'npm-run-all' => '^0.0.5',
        'tailwindcss' => '^0.0.14',
        'tippy.js' => '^6.3.7',
        'vanilla-lazyload' => '^19.1.3',
        'vite' => '^8.0',
    ],

    // Tailwind CSS generation settings
    'tailwind' => [
        'imports' => [],
        'plugins' => [
            '@tailwindcss/typography',
        ],
        'sources' => [
            'resources/views/**/*.blade.php',
        ],
        'validate_sources' => env('CAPELL_TW_VALIDATE_SOURCES', false),
        'output_css' => 'resources/css/capell/frontend.css',

        // When enabled, each theme-css:<key> conditioned import compiles into its
        // own resources/css/capell/themes/<key>.css instead of the shared bundle.
        // Off by default: byte-identical output until enabled and verified in staging.
        'split_theme_css' => env('CAPELL_TW_SPLIT_THEME_CSS', true),
        'theme_css_output_directory' => 'resources/css/capell/themes',
    ],

    'blaze' => [
        'enabled' => env('CAPELL_FOUNDATION_THEME_BLAZE_ENABLED', false),
    ],

    // Media & Storage
    'local_storage_url' => env('CAPELL_LOCAL_STORAGE_URL', ''),
    'use_site_domain_for_media' => env('CAPELL_USE_SITE_DOMAIN_FOR_MEDIA', false),
    'site_base_url' => env('CAPELL_SITE_BASE_URL', ''),
];
