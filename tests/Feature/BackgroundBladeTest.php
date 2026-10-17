<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Blaze\Blaze;

require_once __DIR__ . '/../Support/PublicChromeFixture.php';

use function Capell\FoundationTheme\Tests\Support\chromeElements;
use function Capell\FoundationTheme\Tests\Support\renderPublicChromeView;

it('merges caller styles with background image dimensions without losing either', function (bool $curated): void {
    $media = (new Media)->forceFill(['id' => 1, 'name' => 'Backdrop', 'disk' => 'public', 'file_name' => 'background.jpg']);
    $data = [
        'media' => $curated ? ['alt' => 'Backdrop'] : $media,
        'width' => 320,
        'height' => 200,
        'attributes' => new ComponentAttributeBag(['style' => 'opacity: 0.5', 'class' => 'caller-background']),
    ];
    if ($curated) {
        $data['curatedMedia'] = ['url' => '/curated-background.jpg', 'width' => 320, 'height' => 200];
    }
    $enabled = Blaze::isEnabled();
    Blaze::disable();
    try {
        $html = view()->file(dirname(__DIR__, 2) . '/resources/views/components/media/background.blade.php', $data)->render();
    } finally {
        if ($enabled) {
            Blaze::enable();
        }
    }
    $elements = chromeElements($html, '//div');
    expect($elements)->toHaveCount(1);
    $element = $elements[0];
    expect($element->getAttribute('class'))->toBe('caller-background');

    $declarations = [];
    foreach (explode(';', $element->getAttribute('style')) as $declaration) {
        $parts = explode(':', $declaration, 2);
        if (count($parts) === 2) {
            $declarations[trim($parts[0])] = trim($parts[1]);
        }
    }
    expect($declarations['width'] ?? null)->toBe('320px')
        ->and($declarations['height'] ?? null)->toBe('200px')
        ->and($declarations['opacity'] ?? null)->toBe('0.5')
        ->and($declarations['background-image'] ?? null)->toContain($curated ? '/curated-background.jpg' : 'background.jpg');
})->with(['curated' => true, 'original media' => false]);

it('renders no background when no media is supplied', function (): void {
    expect(trim(renderPublicChromeView('media.background', ['media' => null])))->toBe('');
});
