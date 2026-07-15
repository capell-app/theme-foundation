<?php

declare(strict_types=1);

test('showreel editorial winner feature spans existing grid tracks only', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $showreelCss = file_get_contents($packagesPath . '/theme-showreel/resources/css/theme-showreel.css');

    expect($showreelCss)->toContain('.mva-winners .mva-winner:nth-child(3n + 1)')
        ->and($showreelCss)->toContain('grid-column: 1 / -1;')
        ->and($showreelCss)->not->toContain('grid-column: span 2;');
});

test('photography kinetic gallery reserves a caption row for every plate', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $photographyCss = file_get_contents($packagesPath . '/theme-photography/resources/css/theme-photography.css');

    expect($photographyCss)->toContain('.dlm-kinetic-plate')
        ->and($photographyCss)->toContain('grid-template-rows: minmax(0, 1fr) auto;')
        ->and($photographyCss)->toContain('.dlm-kinetic-frame')
        ->and($photographyCss)->toContain('min-height: 0;')
        ->and($photographyCss)->toContain('.dlm-kinetic-media');
});

test('awards vote gauge scopes its absolute inner mask to the gauge circle', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $awardsCss = file_get_contents($packagesPath . '/theme-awards/resources/css/theme-awards.css');

    expect($awardsCss)->toContain('.sbs-vote-gauge')
        ->and($awardsCss)->toContain('position: relative;')
        ->and($awardsCss)->toContain('overflow: hidden;')
        ->and($awardsCss)->toContain('.sbs-vote-gauge::before')
        ->and($awardsCss)->toContain('position: absolute;');
});

test('awards homepage leaderboard cannot widen mobile screenshots', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $awardsCss = file_get_contents($packagesPath . '/theme-awards/resources/css/theme-awards.css');

    expect($awardsCss)->toContain('.sbs-leaderboard > *')
        ->and($awardsCss)->toContain('min-width: 0;')
        ->and($awardsCss)->toContain('@media (max-width: 767px)')
        ->and($awardsCss)->toContain('grid-template-columns: minmax(0, 1fr);');
});

test('brutalist card grid collapses below mobile screenshot width', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $brutalistCss = file_get_contents($packagesPath . '/theme-brutalist/resources/css/theme-brutalist.css');

    expect($brutalistCss)->toContain('@media (max-width: 767px)')
        ->and($brutalistCss)->toContain('.rwi-grid')
        ->and($brutalistCss)->toContain('grid-template-columns: minmax(0, 1fr);');
});

test('foundation screenshot fixture grids collapse on mobile viewports', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $fixtureRoutes = file_get_contents($packagesPath . '/../workbench/routes/screenshot-fixtures.php');

    expect($fixtureRoutes)->toContain('@media (max-width: 700px)')
        ->and($fixtureRoutes)->toContain('.hero, .facts, .features, .proof, .content-section')
        ->and($fixtureRoutes)->toContain('grid-template-columns: minmax(0, 1fr);')
        ->and($fixtureRoutes)->toContain('.site-nav nav { display: none; }');
});

test('directory and submissions ghost buttons keep readable dark-mode contrast', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $directoryCss = file_get_contents($packagesPath . '/theme-directory/resources/css/theme-directory.css');
    throw_unless(is_string($directoryCss), RuntimeException::class, 'Unable to read Directory theme CSS.');
    $submissionsCss = file_get_contents($packagesPath . '/theme-submissions/resources/css/theme-submissions.css');

    preg_match('/\\.pfd-section-night \\.pfd-button \\{[^}]+\\}/', $directoryCss, $directoryNightButton);

    expect($directoryNightButton[0] ?? '')->toContain('color: var(--pfd-night);')
        ->and($directoryNightButton[0] ?? '')->not->toContain('color: var(--pfd-ink);');

    expect($submissionsCss)->toContain('@media (prefers-color-scheme: dark)')
        ->and($submissionsCss)->toContain('.exd-shell .exd-section-paper .exd-button-secondary')
        ->and($submissionsCss)->toContain('color: var(--exd-ink);')
        ->and($submissionsCss)->toContain('border-color: var(--exd-line);');
});

test('directory card density container queries compile without comma warnings', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $directoryCss = file_get_contents($packagesPath . '/theme-directory/resources/css/theme-directory.css');

    expect($directoryCss)
        ->toContain('@container pfd-tokens style(--theme-card-density: compact)')
        ->toContain('@container pfd-tokens style(--theme-card-density: dense)')
        ->not->toContain('@container pfd-tokens style(--theme-card-density: compact),');
});

test('editorial story topology gives orbital cards enough room before falling back to a list', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $editorialCss = file_get_contents($packagesPath . '/theme-editorial/resources/css/theme-editorial.css');

    expect($editorialCss)->toContain('.dnews-topology')
        ->and($editorialCss)->toContain('min-height: clamp(32rem, 58cqi, 40rem);')
        ->and($editorialCss)->toContain('clamp(14rem, 28cqi, 19rem)')
        ->and($editorialCss)->toContain('transform: translate(-50%, -50%) rotate(var(--dnews-topology-angle))')
        ->and($editorialCss)->toContain('@container (max-width: 52rem)');
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

test('platform contact screenshots wait for the shared contact form', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $manifest = file_get_contents($packagesPath . '/theme-platform/docs/screenshots.json');
    throw_unless(is_string($manifest), RuntimeException::class, 'Unable to read Platform screenshot manifest.');

    preg_match_all('/"id": "platform-contact(?:-tablet|-mobile)?".+?"waitFor": "([^"]+)"/s', $manifest, $contactWaits);

    expect($contactWaits[1])->toBe(['#contact-form', '#contact-form', '#contact-form']);
});

test('saas mobile screenshots clip horizontal rails to the viewport', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $saasCss = file_get_contents($packagesPath . '/theme-saas/resources/css/theme-saas.css');

    expect($saasCss)->toContain('@media (max-width: 767px)')
        ->and($saasCss)->toContain('.lga-shell')
        ->and($saasCss)->toContain('overflow-x: clip;')
        ->and($saasCss)->toContain('.lga-grid-rail')
        ->and($saasCss)->toContain('max-inline-size: 100%;')
        ->and($saasCss)->toContain('.lga-rail-card')
        ->and($saasCss)->toContain('flex-basis: min(82vw, 20rem);');
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
