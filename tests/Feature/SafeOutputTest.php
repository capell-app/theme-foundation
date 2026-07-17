<?php

declare(strict_types=1);

use Capell\FoundationTheme\View\Components\Actions;
use Capell\Frontend\Actions\Performance\RecordExtensionRenderContributionAction;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;

test('default theme escapes site titles and plain footer text', function (): void {
    $themePath = dirname(__DIR__, 2);

    $header = file_get_contents($themePath . '/resources/views/components/header/index.blade.php');
    $footer = file_get_contents($themePath . '/resources/views/components/footer/index.blade.php');
    $relatedSites = file_get_contents($themePath . '/resources/views/components/footer/related-sites.blade.php');
    $siteInfo = file_get_contents($themePath . '/resources/views/components/footer/site-info.blade.php');

    expect($footer)->toContain('RenderHtmlContentAction::run(Lang::get($footerCopy');
    expect($header)->not->toContain('{!! $site->translation->title !!}');
    expect($siteInfo)->not->toContain('{!! $site->translation->title !!}');
    expect($relatedSites)->not->toContain('{!! $relatedSite->translation->title !!}');
    expect($relatedSites)->not->toContain('{!! $description !!}');
    expect($footer)->not->toContain('{!!' . PHP_EOL . '                Lang::get($footerCopy');
});

test('content component sanitizes cms html before rendering', function (): void {
    $themePath = dirname(__DIR__, 2);

    $content = file_get_contents($themePath . '/resources/views/components/content.blade.php');

    expect($content)->toContain('RenderHtmlContentAction::run($content, $pageVariables)')
        ->and($content)->not->toContain('{!! $content !!}')
        ->and($content)->not->toContain('{!! $page->translation->content !!}')
        ->and($content)->not->toContain('onkeydown=')
        ->and($content)->toContain("'imageLoading' => 'lazy'")
        ->and($content)->toContain("'imageFetchPriority' => 'auto'");
});

test('layout native foundation sections expose stable buyer journey anchors', function (): void {
    $sectionComponent = file_get_contents(
        dirname(__DIR__, 2) . '/src/View/Components/Widget/FoundationSection.php',
    );
    $sectionRenderer = file_get_contents(
        dirname(__DIR__, 2) . '/resources/views/components/widget/foundation-section.blade.php',
    );

    expect($sectionComponent)->not->toBeFalse()
        ->and($sectionComponent)->toContain('ANCHORABLE_SECTION_TYPES')
        ->and($sectionComponent)->toContain('ResolveFoundationSectionAnchorAction::run(')
        ->and($sectionRenderer)->not->toBeFalse()
        ->and($sectionRenderer)->toContain('$anchorable')
        ->and($sectionRenderer)->toContain('id="{{ $sectionAnchor }}"');
});

test('the public skip link keeps an explicit high contrast focus indicator', function (): void {
    $layout = file_get_contents(
        dirname(__DIR__, 2) . '/resources/views/components/layout/index.blade.php',
    );
    $themeCss = file_get_contents(
        dirname(__DIR__, 2) . '/resources/css/theme-foundation.css',
    );

    expect($layout)->not->toBeFalse()
        ->and($layout)->toContain('capell-public-skip-link')
        ->and($themeCss)->not->toBeFalse()
        ->and($themeCss)->toContain('.capell-public-skip-link:focus')
        ->and($themeCss)->toContain('outline: 3px solid var(--foundation-focus-ring, #1d4ed8) !important')
        ->and($themeCss)->toContain('outline-color: Highlight !important');
});

test('public buttons expose finite safe states and sanitize destinations', function (): void {
    $button = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/button/index.blade.php');

    if (! is_string($button)) {
        throw new RuntimeException('Unable to read the public Foundation button component.');
    }

    expect(substr_count($button, "'color' =>"))->toBe(1)
        ->and($button)->toContain('PublicUrlSanitizer::sanitize($url)')
        ->toContain("'type' => 'button'")
        ->toContain('aria-busy="true"')
        ->toContain('aria-disabled="true"')
        ->toContain('noopener noreferrer');
});

test('public image source component renders URL images without admin metadata', function (): void {
    $html = Blade::render(
        '<x-capell::image-source image="/public-image.jpg" alt="Public image" />',
    );

    expect($html)
        ->toContain('src="/public-image.jpg"')
        ->toContain('alt="Public image"')
        ->toContain('capell-image-source')
        ->not->toContain('recordId')
        ->not->toContain('fieldPath')
        ->not->toContain('wire:');
});

test('default theme treats navigation as optional', function (): void {
    $themePath = dirname(__DIR__, 2);

    $header = file_get_contents($themePath . '/resources/views/components/header/index.blade.php');
    $footer = file_get_contents($themePath . '/resources/views/components/footer/index.blade.php');
    $footerComponent = file_get_contents($themePath . '/src/View/Components/Footer/Index.php');

    expect($header)->toContain("scenario: 'theme-foundation-primary-navigation'")
        ->and($header)->not->toContain('NavigationAvailability::check()')
        ->and($header)->not->toContain('if ($navigationAvailable)')
        ->and($footerComponent)->toContain('NavigationAvailability::check()')
        ->and($footerComponent)->toContain('$navigationAvailable')
        ->and($footer)->not->toContain('NavigationAvailability::check()');
});

test('public layout output does not include debug widget comments', function (): void {
    $themePath = dirname(__DIR__, 2);

    $container = file_get_contents($themePath . '/resources/views/components/layout/container.blade.php');

    expect($container)
        ->toContain('...$presentation->classes()')
        ->not->toContain('theme_settings')
        ->not->toContain('surface_tone')
        ->not->toContain('<!-- {$widget->key} Widget')
        ->not->toContain("config('app.debug')");
});

test('language flag images reserve dimensions for stable public layout', function (): void {
    $themePath = dirname(__DIR__, 2);
    $files = [
        'resources/views/components/languages.blade.php',
        'resources/views/components/header/menu/languages-dropdown.blade.php',
    ];
    $violations = [];

    foreach ($files as $file) {
        $contents = file_get_contents($themePath . '/' . $file);

        preg_match_all('/<img\b.*?vendor\/blade-country-flags.*?\/>/s', (string) $contents, $matches);

        foreach ($matches[0] as $imageTag) {
            if (! str_contains($imageTag, 'width="16"') || ! str_contains($imageTag, 'height="16"')) {
                $violations[] = $file . ' contains an unsized language flag image';
            }
        }
    }

    expect($violations)->toBe(
        [],
        'Language flag images without explicit dimensions found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

test('public action buttons mark their csrf output as non-cacheable', function (): void {
    Route::post('/public-actions/{action}', static fn (): string => 'ok')
        ->name('capell-public-actions.submit');
    Route::getRoutes()->refreshNameLookups();

    resolve(RecordExtensionRenderContributionAction::class)->clear();

    (new Actions(actions: [
        [
            'type' => 'public_action',
            'public_action_key' => 'request-access',
            'label' => 'Request access',
        ],
    ]))->render();

    $contribution = collect(resolve(RecordExtensionRenderContributionAction::class)->recorded())
        ->first(fn (mixed $record): bool => $record->contributionClass === Actions::class);

    expect($contribution?->cacheable)->toBeFalse()
        ->and($contribution?->sensitiveOutput)->toBeTrue();
});

test('public blade keeps data loading out of templates', function (): void {
    $themePath = dirname(__DIR__, 2);
    $violations = [];
    $forbiddenPatterns = [
        'DB::',
        '::query(',
        'NavigationLoader::',
        'PageLoader::',
        'SiteLoader::',
        'Frontend::site()->siteDomain',
        'loadMissing(',
        'relationLoaded(',
        'getMedia(',
    ];

    $files = (new Finder)
        ->files()
        ->in($themePath . '/resources/views')
        ->name('*.blade.php')
        ->notPath('components/filament')
        ->notPath('components/infolists');

    foreach ($files as $file) {
        $contents = $file->getContents();
        $relativePath = str_replace($themePath . '/', '', $file->getPathname());

        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = $relativePath . ' contains ' . $pattern;
            }
        }
    }

    expect($violations)->toBe(
        [],
        'Public Blade data-loading violations found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

test('public blade getMeta and translation relation reads stay reviewed', function (): void {
    $themePath = dirname(__DIR__, 2);
    $reviewedAccess = [
        'resources/views/app.blade.php' => ['getMeta' => 2, 'translation' => 0],
        'resources/views/components/app/body.blade.php' => ['getMeta' => 2, 'translation' => 0],
        'resources/views/components/app/head/tokens.blade.php' => ['getMeta' => 4, 'translation' => 0],
        'resources/views/components/content.blade.php' => ['getMeta' => 1, 'translation' => 0],
        'resources/views/components/demo/contact-page.blade.php' => ['getMeta' => 0, 'translation' => 2],
        'resources/views/components/footer/index.blade.php' => ['getMeta' => 1, 'translation' => 0],
        'resources/views/components/footer/site-info.blade.php' => ['getMeta' => 4, 'translation' => 0],
        'resources/views/components/header/index.blade.php' => ['getMeta' => 13, 'translation' => 1],
        'resources/views/components/layout/index.blade.php' => ['getMeta' => 1, 'translation' => 3],
        'resources/views/components/layout/main.blade.php' => ['getMeta' => 2, 'translation' => 0],
        'resources/views/components/logo/index.blade.php' => ['getMeta' => 0, 'translation' => 1],
        'resources/views/components/section/team-member.blade.php' => ['getMeta' => 1, 'translation' => 2],
        'resources/views/components/widget/announcement-bar.blade.php' => ['getMeta' => 4, 'translation' => 2],
        'resources/views/components/widget/asset/accordion.blade.php' => ['getMeta' => 6, 'translation' => 3],
        'resources/views/components/widget/asset/banners.blade.php' => ['getMeta' => 1, 'translation' => 0],
        'resources/views/components/widget/asset/carousel.blade.php' => ['getMeta' => 22, 'translation' => 10],
        'resources/views/components/widget/asset/features.blade.php' => ['getMeta' => 10, 'translation' => 3],
        'resources/views/components/widget/asset/index.blade.php' => ['getMeta' => 16, 'translation' => 3],
        'resources/views/components/widget/asset/media.blade.php' => ['getMeta' => 9, 'translation' => 3],
        'resources/views/components/widget/asset/pages.blade.php' => ['getMeta' => 17, 'translation' => 6],
        'resources/views/components/widget/asset/testimonials.blade.php' => ['getMeta' => 21, 'translation' => 9],
        'resources/views/components/widget/asset/widgets.blade.php' => ['getMeta' => 12, 'translation' => 3],
        'resources/views/components/widget/banner-image.blade.php' => ['getMeta' => 8, 'translation' => 2],
        'resources/views/components/widget/default.blade.php' => ['getMeta' => 10, 'translation' => 2],
        'resources/views/components/widget/hero.blade.php' => ['getMeta' => 2, 'translation' => 2],
        'resources/views/components/widget/kitchen-sink/reference.blade.php' => ['getMeta' => 2, 'translation' => 3],
        'resources/views/components/widget/modern/alternating-content.blade.php' => ['getMeta' => 0, 'translation' => 5],
        'resources/views/components/widget/modern/card-grid.blade.php' => ['getMeta' => 3, 'translation' => 5],
        'resources/views/components/widget/modern/cta-section.blade.php' => ['getMeta' => 4, 'translation' => 2],
        'resources/views/components/widget/modern/faq-section.blade.php' => ['getMeta' => 2, 'translation' => 5],
        'resources/views/components/widget/modern/feature-list.blade.php' => ['getMeta' => 2, 'translation' => 5],
        'resources/views/components/widget/modern/hero-banner.blade.php' => ['getMeta' => 5, 'translation' => 2],
        'resources/views/components/widget/modern/image-gallery.blade.php' => ['getMeta' => 1, 'translation' => 2],
        'resources/views/components/widget/modern/pricing-table.blade.php' => ['getMeta' => 8, 'translation' => 5],
        'resources/views/components/widget/modern/process-steps.blade.php' => ['getMeta' => 3, 'translation' => 5],
        'resources/views/components/widget/modern/stats-section.blade.php' => ['getMeta' => 2, 'translation' => 5],
        'resources/views/components/widget/modern/team-members.blade.php' => ['getMeta' => 1, 'translation' => 5],
        'resources/views/components/widget/modern/testimonials.blade.php' => ['getMeta' => 6, 'translation' => 5],
        'resources/views/components/widget/navigation/index.blade.php' => ['getMeta' => 4, 'translation' => 0],
        'resources/views/components/widget/page/breadcrumbs.blade.php' => ['getMeta' => 0, 'translation' => 2],
        'resources/views/components/widget/page/content.blade.php' => ['getMeta' => 6, 'translation' => 2],
        'resources/views/components/widget/snippet.blade.php' => ['getMeta' => 3, 'translation' => 2],
        'resources/views/components/widget/wrapper.blade.php' => ['getMeta' => 9, 'translation' => 0],
    ];
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($themePath . '/resources/views')
        ->name('*.blade.php')
        ->notPath('components/filament')
        ->notPath('components/infolists');

    foreach ($files as $file) {
        $contents = $file->getContents();
        $relativePath = str_replace($themePath . '/', '', $file->getPathname());

        preg_match_all('/getMeta\(/', $contents, $getMetaMatches);
        preg_match_all('/(?:\?->|->)translation\b/', $contents, $translationMatches);

        $actualAccess = [
            'getMeta' => count($getMetaMatches[0]),
            'translation' => count($translationMatches[0]),
        ];
        $allowedAccess = $reviewedAccess[$relativePath] ?? ['getMeta' => 0, 'translation' => 0];

        foreach ($actualAccess as $accessType => $count) {
            if ($count > $allowedAccess[$accessType]) {
                $violations[] = sprintf(
                    '%s contains %d reviewed %s reads, found %d',
                    $relativePath,
                    $allowedAccess[$accessType],
                    $accessType,
                    $count,
                );
            }
        }
    }

    expect($violations)->toBe(
        [],
        'Unreviewed public Blade getMeta or translation relation reads found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

test('demo contact page uses only the prepared current domain relation', function (): void {
    $contactPage = file_get_contents(
        dirname(__DIR__, 2) . '/resources/views/components/demo/contact-page.blade.php',
    );

    expect($contactPage)
        ->not->toContain('->defaultDomain')
        ->toContain('$site->siteDomain?->url');
});

test('theme public views and assets avoid package and theme implementation markers', function (): void {
    $packagesRoot = dirname(__DIR__, 3);
    $packagePaths = [
        $packagesRoot . '/theme-foundation',
        ...glob($packagesRoot . '/theme-*') ?: [],
    ];
    $directories = [];
    $forbiddenMarkers = [
        'data-theme-key',
        'data-capell-theme',
        'capell-theme capell-theme-',
        'class="capell-theme',
        '.capell-theme-',
        '--capell-theme-',
        'capell-theme-foundation-',
        'capell-app-body',
        'capell-app/theme-',
        'capell-app/theme-foundation',
        'model_id',
        'field_path',
    ];
    $violations = [];

    foreach ($packagePaths as $packagePath) {
        foreach (['resources/views', 'resources/css', 'resources/js'] as $relativeDirectory) {
            $directory = $packagePath . '/' . $relativeDirectory;

            if (is_dir($directory)) {
                $directories[] = $directory;
            }
        }
    }

    $files = (new Finder)
        ->files()
        ->in($directories)
        ->name(['*.blade.php', '*.css', '*.js']);

    foreach ($files as $file) {
        $contents = $file->getContents();
        $relativePath = str_replace($packagesRoot . '/', '', $file->getPathname());

        // The Inertia themes ship a client-side public-HTML sanitizer whose denylist
        // regex must name the very markers it strips (model_id, field_path). Naming a
        // token in order to remove it is the opposite of leaking it, so exempt that
        // single file from those two markers while keeping every other marker enforced.
        $isMarkerSanitizer = str_ends_with($relativePath, 'resources/js/Support/publicHtml.js');

        foreach ($forbiddenMarkers as $marker) {
            if ($isMarkerSanitizer && in_array($marker, ['model_id', 'field_path'], true)) {
                continue;
            }

            if (str_contains($contents, $marker)) {
                $violations[] = $relativePath . ' contains ' . $marker;
            }
        }
    }

    expect($violations)->toBe(
        [],
        'Theme public output marker leaks found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

test('reviewed public blade widgets do not read asset and page relations directly', function (): void {
    $themePath = dirname(__DIR__, 2);
    $files = [
        'resources/views/components/widget/modern/hero-banner.blade.php',
        'resources/views/components/widget/modern/image-gallery.blade.php',
        'resources/views/components/widget/modern/card-grid.blade.php',
        'resources/views/components/widget/asset/accordion.blade.php',
        'resources/views/components/widget/asset/carousel.blade.php',
        'resources/views/components/widget/asset/feature-item.blade.php',
        'resources/views/components/widget/asset/media.blade.php',
        'resources/views/components/widget/asset/widgets.blade.php',
    ];
    $forbiddenPatterns = [
        '$page?->assets',
        '$attachment->asset',
        '$heroItem->asset',
        '$widget->assets',
        '$asset->asset->media',
        '$asset->asset->translation',
        '$widgetAsset->asset->translation',
        '$widgetAsset->asset->getMeta(',
        '$linkedPage->pageUrl',
    ];
    $violations = [];

    foreach ($files as $file) {
        $contents = file_get_contents($themePath . '/' . $file);

        if (! is_string($contents)) {
            throw new RuntimeException(sprintf('Expected %s to be readable.', $file));
        }

        foreach ($forbiddenPatterns as $pattern) {
            if (str_contains($contents, $pattern)) {
                $violations[] = $file . ' contains ' . $pattern;
            }
        }
    }

    expect($violations)->toBe(
        [],
        'Reviewed public Blade relation access found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

test('ap hero and gallery public output avoid reviewed accessibility and editor copy regressions', function (): void {
    $themePath = dirname(__DIR__, 2);
    $hero = file_get_contents($themePath . '/resources/views/components/widget/modern/hero-banner.blade.php');
    $gallery = file_get_contents($themePath . '/resources/views/components/widget/modern/image-gallery.blade.php');
    $cardGrid = file_get_contents($themePath . '/resources/views/components/widget/modern/card-grid.blade.php');
    $pageContent = file_get_contents($themePath . '/resources/views/components/widget/page/content.blade.php');
    $pageCollection = file_get_contents($themePath . '/resources/views/components/widget/asset/pages.blade.php');
    $themeStyles = file_get_contents($themePath . '/resources/css/theme/theme.css');

    expect($hero)->toContain('MarkPrimaryHeadingRenderedAction::run()')
        ->and($pageContent)->toContain("\$headingTag = (\$hasPrimaryHeading ? 'h2' : 'h1');")
        ->and($pageContent)->not->toContain('prose-headings:text-slate-950')
        ->and($pageContent)->not->toContain('text-slate-700')
        ->and($themeStyles)->toContain('--tw-prose-body: var(--foundation-body-fg)')
        ->and($themeStyles)->toContain('.content-component.capell-standard-page-content')
        ->and($themeStyles)->toMatch('/\.dark \.content-component\.capell-standard-page-content.*?--tw-prose-body: var\(--color-slate-300\).*?--tw-prose-headings: var\(--color-slate-50\)/s')
        ->and($themeStyles)->toContain('color: var(--color-slate-50) !important')
        ->and($pageCollection)->toContain(":heading-tag=\"\$showPageTitle ? 'h1' : \$widget->getMeta('heading_tag', 'h2')\"")
        ->and($hero)->not->toContain('ap-hero__slideshow-play')
        ->and($gallery)->not->toContain('No images configured')
        ->and($cardGrid)->not->toContain('No cards configured');
});

test('theme public blade avoids unstable identifier helpers', function (): void {
    $themePath = dirname(__DIR__, 2);
    $contentListing = file_get_contents($themePath . '/resources/views/theme/sections/content-listing.blade.php');

    expect($contentListing)
        ->not->toContain('md5(')
        ->toContain("hash('xxh128'");
});

test('reviewed foundation chrome avoids accessibility regressions', function (): void {
    $themePath = dirname(__DIR__, 2);
    $footer = file_get_contents($themePath . '/resources/views/components/footer/index.blade.php');
    $socialLinks = file_get_contents($themePath . '/resources/views/components/footer/social-links.blade.php');
    $relatedSites = file_get_contents($themePath . '/resources/views/components/footer/related-sites.blade.php');
    $languages = file_get_contents($themePath . '/resources/views/components/languages.blade.php');
    $dropdown = file_get_contents($themePath . '/resources/views/components/dropdown/index.blade.php');

    expect($footer)->not->toContain('href="javascript:void(0)"')
        ->and($footer)->toContain('type="button"')
        ->and($footer)->toContain("__('capell-theme-foundation::generic.scroll_to_top')")
        ->and($socialLinks)->toContain('rel="nofollow noopener"')
        ->and($relatedSites)->not->toContain('role="menu"')
        ->and($relatedSites)->not->toContain('role="menuitem"')
        ->and($relatedSites)->toContain('ResolveSafeCssColorTokenAction::run')
        ->and($relatedSites)->toContain('border-[var(--border-color-footer)]')
        ->and($footer)->toContain('footer.related-sites')
        ->and($footer)->toContain('$containerWidth->getContainerClass()')
        ->and($languages)->not->toContain('role="menuitem"')
        ->and($languages)->toContain("__('capell-theme-foundation::generic.change_language')")
        ->and($languages)->toContain(':dark-mode="false"')
        ->and($languages)->toContain('alt=""')
        ->and($dropdown)->toContain('x-float')
        ->and($dropdown)->toContain('$floatingDropdownRef')
        ->and($dropdown)->toContain('.toggle(\$event)');
});

test('public blade style tokens use css color safety resolver', function (): void {
    $themePath = dirname(__DIR__, 2);
    $files = [
        'resources/views/components/footer/index.blade.php',
        'resources/views/components/header/index.blade.php',
        'resources/views/components/layout/main.blade.php',
    ];
    $violations = [];

    $headTokens = file_get_contents($themePath . '/resources/views/components/app/head/tokens.blade.php');
    $tokenAction = file_get_contents($themePath . '/src/Actions/ResolveFoundationThemeTokensAction.php');

    if (! is_string($headTokens) || ! str_contains($headTokens, 'ResolveFoundationThemeTokensAction::run')) {
        $violations[] = 'resources/views/components/app/head/tokens.blade.php does not use ResolveFoundationThemeTokensAction::run';
    }

    if (! is_string($tokenAction) || ! str_contains($tokenAction, 'ResolveSafeCssColorTokenAction::run')) {
        $violations[] = 'src/Actions/ResolveFoundationThemeTokensAction.php does not use ResolveSafeCssColorTokenAction::run';
    }

    foreach ($files as $file) {
        $contents = file_get_contents($themePath . '/' . $file);

        if (! is_string($contents)) {
            throw new RuntimeException(sprintf('Expected %s to be readable.', $file));
        }

        if (! str_contains($contents, 'ResolveSafeCssColorTokenAction::run')) {
            $violations[] = $file . ' does not use ResolveSafeCssColorTokenAction::run';
        }

        if (str_contains($contents, 'ColorConverterAction::run')) {
            $violations[] = $file . ' calls ColorConverterAction::run directly';
        }
    }

    expect($violations)->toBe(
        [],
        'Unsafe public Blade style-token resolvers found:' . PHP_EOL .
        json_encode($violations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});
