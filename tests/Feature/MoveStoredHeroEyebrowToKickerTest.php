<?php

declare(strict_types=1);

use Capell\Core\Data\UpgradeContext;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\FoundationTheme\Actions\MoveStoredHeroEyebrowToKickerAction;
use Capell\FoundationTheme\Support\Upgrade\MoveHeroEyebrowToKickerUpgradeStep;
use Capell\LayoutBuilder\Models\Widget;

/*
 * Bug caught: the Platform, Editorial and Portfolio heroes stopped reading the
 * `eyebrow` field, so a label an owner saved there vanished from the page.
 */
it('keeps a hero label saved under the old field, once', function (): void {
    $stored = Widget::factory()->create([
        'key' => 'owner-hero',
        'component' => 'capell.widget.editorial.hero',
        'meta' => ['type' => 'hero', 'heading' => 'Owner headline', 'eyebrow' => 'Owner label'],
    ]);
    $alreadyLabelled = Widget::factory()->create([
        'key' => 'labelled-hero',
        'component' => 'capell.widget.platform.hero',
        'meta' => ['type' => 'hero', 'heading' => 'Owner headline', 'kicker' => 'Chosen label', 'eyebrow' => 'Old label'],
    ]);
    $otherTheme = Widget::factory()->create([
        'key' => 'other-theme-hero',
        'component' => 'capell.widget.agency.hero',
        'meta' => ['type' => 'hero', 'heading' => 'Owner headline', 'eyebrow' => 'Stays put'],
    ]);
    $page = Page::factory()->site(Site::factory()->create())->create([
        'meta' => ['theme_demo' => ['theme_key' => 'portfolio', 'render_data' => ['sections' => [
            ['type' => 'hero', 'heading' => 'Owner headline', 'eyebrow' => 'Page label'],
            ['type' => 'proof', 'eyebrow' => 'Not a hero'],
        ]]]],
    ]);
    $step = resolve(MoveHeroEyebrowToKickerUpgradeStep::class);
    $context = new UpgradeContext(composerVersions: [], ledgerVersions: [], appliedStepIds: []);

    expect($step->shouldRun($context))->toBeTrue()
        ->and($step->run($context))->toBeTrue();

    $sections = data_get($page->refresh()->meta, 'theme_demo.render_data.sections');

    expect($stored->refresh()->meta)->toBe(['type' => 'hero', 'heading' => 'Owner headline', 'kicker' => 'Owner label'])
        ->and($alreadyLabelled->refresh()->meta)->toBe(['type' => 'hero', 'heading' => 'Owner headline', 'kicker' => 'Chosen label'])
        ->and($otherTheme->refresh()->meta)->toBe(['type' => 'hero', 'heading' => 'Owner headline', 'eyebrow' => 'Stays put'])
        ->and($sections)->toBe([
            ['type' => 'hero', 'heading' => 'Owner headline', 'kicker' => 'Page label'],
            ['type' => 'proof', 'eyebrow' => 'Not a hero'],
        ])
        ->and(MoveStoredHeroEyebrowToKickerAction::run())->toBe(0);
});

it('leaves the stored label alone on a dry run', function (): void {
    $stored = Widget::factory()->create([
        'key' => 'dry-run-hero',
        'component' => 'capell.widget.portfolio.hero',
        'meta' => ['type' => 'hero', 'eyebrow' => 'Owner label'],
    ]);

    resolve(MoveHeroEyebrowToKickerUpgradeStep::class)->run(
        new UpgradeContext(composerVersions: [], ledgerVersions: [], appliedStepIds: [], dryRun: true),
    );

    expect($stored->refresh()->meta)->toBe(['type' => 'hero', 'eyebrow' => 'Owner label']);
});
