<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Illuminate\Support\Facades\Blade;
use Livewire\Blaze\Blaze;

it('renders site contact details from meta', function (): void {
    Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');

    $siteDomain = new SiteDomain([
        'domain' => 'example.test',
        'path' => null,
        'scheme' => 'https',
    ]);

    $site = new Site([
        'name' => 'Capell Ruby',
        'meta' => [
            'business_name' => 'Capell Ltd',
            'email' => 'hello@example.test',
            'phone' => '+44 20 7946 0958',
        ],
    ]);
    $site->setRelation('siteDomain', $siteDomain);
    $site->setRelation('translation', new Translation([
        'title' => 'Capell Ruby',
        'meta' => ['tagline' => 'Boringly reliable CMS foundations'],
    ]));

    $contactPage = new Page(['name' => 'Contact']);
    $contactPageUrl = new PageUrl(['url' => '/contact']);
    $contactPageUrl->setRelation('siteDomain', $siteDomain);

    $contactPage->setRelation('pageUrl', $contactPageUrl);
    $contactPage->setRelation('translation', new Translation([
        'title' => 'Talk to us',
        'meta' => ['label' => 'Talk to us'],
    ]));

    $view = view('capell::components.footer.site-info', [
        'site' => $site,
        'contactPage' => $contactPage,
    ]);
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        $html = $view->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect($html)
        ->toContain('Capell Ruby')
        ->toContain('Boringly reliable CMS foundations')
        ->toContain('Capell Ltd')
        ->toContain('mailto:hello@example.test')
        ->toContain('tel:+442079460958')
        ->toContain('Talk to us');
});

it('falls back to the site name when the translation relation is not hydrated', function (): void {
    Blade::anonymousComponentPath(__DIR__ . '/../../resources/views/components', 'capell');

    $siteDomain = new SiteDomain([
        'domain' => 'example.test',
        'path' => null,
        'scheme' => 'https',
    ]);

    $site = new Site(['name' => 'Fallback Site']);
    $site->setRelation('siteDomain', $siteDomain);

    $view = view('capell::components.footer.site-info', [
        'site' => $site,
        'contactPage' => null,
    ]);
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        $html = $view->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }

    expect($html)
        ->toContain('Fallback Site')
        ->not->toContain('footer-tagline');
});
