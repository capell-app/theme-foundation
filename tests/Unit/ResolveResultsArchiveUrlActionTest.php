<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\FoundationTheme\Actions\ResolveResultsArchiveUrlAction;

it('resolves the first published archive with an enabled URL for the requested language', function (): void {
    $english = Language::factory()->english()->create();
    $french = Language::factory()->french()->create();
    $site = Site::factory()->create(['language_id' => $english->getKey()]);
    SiteDomain::factory()->default()->site($site)->language($french)->create();
    $layout = Layout::factory()->site($site)->create(['key' => 'archives']);

    $draft = Page::factory()->site($site)->layout($layout)->pending()->create();
    PageUrl::factory()->site($site)->page($draft)->language($french)->create(['url' => '/fr/brouillon']);

    $firstPublished = Page::factory()->site($site)->layout($layout)->published()->create();
    PageUrl::factory()->site($site)->page($firstPublished)->language($english)->create(['url' => '/archive']);
    PageUrl::factory()->site($site)->page($firstPublished)->language($french)->create(['url' => '/fr/archives']);

    $laterPublished = Page::factory()->site($site)->layout($layout)->published()->create();
    PageUrl::factory()->site($site)->page($laterPublished)->language($french)->create(['url' => '/fr/archives-2']);

    $disabled = Page::factory()->site($site)->layout($layout)->published()->create();
    PageUrl::factory()->site($site)->page($disabled)->language($french)->create([
        'url' => '/fr/desactivee',
        'status' => false,
    ]);

    $url = resolve(ResolveResultsArchiveUrlAction::class)->handle($site, $french);

    expect($url)->toEndWith('/fr/archives');
});
