<?php

declare(strict_types=1);

return [
    // Asset build tool: 'vite' | 'mix' | 'static'
    'asset_build_tool' => 'vite',

    // NPM dependencies required by the default theme
    'npm_dependencies' => [
        '@tailwindcss/typography' => '^0.5.9',
        '@tailwindcss/vite' => '^4.0.13',
        'autoprefixer' => '^10.4.13',
        'swiper' => '^12.1.2',
        'fontaine' => '^0.8.0',
        'laravel-vite-plugin' => '^3.1.3',
        'npm-run-all' => '^4.1.5',
        'tailwindcss' => '^4.0.14',
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
        // On by default. Set CAPELL_TW_SPLIT_THEME_CSS=false only as a rollback.
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
