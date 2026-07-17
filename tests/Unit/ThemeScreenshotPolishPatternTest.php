<?php

declare(strict_types=1);

test('directory card density container queries compile without comma warnings', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $directoryCss = file_get_contents($packagesPath . '/theme-directory/resources/css/theme-directory.css');

    expect($directoryCss)
        ->toContain('@container pfd-tokens style(--theme-card-density: compact)')
        ->toContain('@container pfd-tokens style(--theme-card-density: dense)')
        ->not->toContain('@container pfd-tokens style(--theme-card-density: compact),');
});

test('catalogue onepage and saas navigation ctas fall back from empty labels', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $catalogueNavigation = file_get_contents($packagesPath . '/theme-catalogue/resources/views/sections/navigation.blade.php');
    $onepageNavigation = file_get_contents($packagesPath . '/theme-onepage/resources/views/sections/navigation.blade.php');
    $onepageTranslations = file_get_contents($packagesPath . '/theme-onepage/resources/lang/en/sections.php');
    $saasNavigation = file_get_contents($packagesPath . '/theme-saas/resources/views/sections/navigation.blade.php');

    expect($catalogueNavigation)->toContain("filled(\$ctaLabel) ? \$ctaLabel : __('capell-theme-catalogue::sections.navigation.cta')")
        ->and($onepageNavigation)->toContain("filled(\$ctaLabel) ? \$ctaLabel : __('capell-theme-onepage::sections.navigation.cta')")
        ->and($onepageTranslations)->toContain("'cta' => 'Submit a one-pager'")
        ->and($saasNavigation)->toContain("filled(\$ctaLabel) ? \$ctaLabel : __('capell-theme-saas::sections.navigation.cta')");
});

test('theme section navigations expose the shared mobile disclosure menu', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $themes = [
        'agency',
        'awards',
        'brutalist',
        'catalogue',
        'curated',
        'directory',
        'editorial',
        'magazine',
        'minimalist',
        'onepage',
        'photography',
        'platform',
        'portfolio',
        'saas',
        'showreel',
        'submissions',
    ];

    foreach ($themes as $theme) {
        $navigation = file_get_contents($packagesPath . "/theme-{$theme}/resources/views/sections/navigation.blade.php");
        $css = file_get_contents($packagesPath . "/theme-{$theme}/resources/css/theme-{$theme}.css");

        expect($navigation)
            ->toContain('capell-desktop-nav')
            ->toContain("view('capell-theme-foundation::theme.partials.mobile-navigation'");

        if ($theme === 'editorial') {
            expect($css)->toContain("@import '../../../theme-foundation/resources/css/theme/chrome.css';");
        } else {
            expect($css)
                ->toContain('.capell-mobile-nav')
                ->toMatch('/@media \(max-width: (?:767px|1023px)\)/')
                ->toContain('.capell-desktop-nav-action');
        }
    }

    $sharedChrome = file_get_contents($packagesPath . '/theme-foundation/resources/css/theme/chrome.css');

    expect($sharedChrome)
        ->toContain('.capell-mobile-nav')
        ->toContain('@media (max-width: 767px)')
        ->toContain('.capell-desktop-nav-action');
});

test('platform contact screenshots wait for the rendered contact hero', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $manifest = file_get_contents($packagesPath . '/theme-platform/docs/screenshots.json');
    throw_unless(is_string($manifest), RuntimeException::class, 'Unable to read Platform screenshot manifest.');

    preg_match_all('/"id": "platform-contact(?:-tablet|-mobile)?".+?"waitFor": "([^"]+)"/s', $manifest, $contactWaits);

    // The Platform contact surface renders heroSection() (id="hero") and has no
    // form element — its CTAs are mailto links. Waiting for "#contact-form"
    // would time out during capture.
    expect($contactWaits[1])->toBe(['#hero', '#hero', '#hero']);
});

test('submissions contact and cta demo pages seed distinct page records', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $submissionsContent = file_get_contents($packagesPath . '/theme-submissions/src/Support/Demo/SubmissionsDemoContent.php');

    expect($submissionsContent)->toContain("surface: 'contact',")
        ->and($submissionsContent)->toContain("name: self::BRAND . ' Submit',")
        ->and($submissionsContent)->toContain("slug: 'theme-' . \$themeKey . '-contact',")
        ->and($submissionsContent)->toContain("surface: 'cta',")
        ->and($submissionsContent)->toContain("name: self::BRAND . ' Conversion',")
        ->and($submissionsContent)->toContain("slug: 'theme-' . \$themeKey . '-cta',");
});
