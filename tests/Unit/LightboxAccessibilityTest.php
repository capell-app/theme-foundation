<?php

declare(strict_types=1);
use Livewire\Blaze\Blaze;
use Symfony\Component\Process\Process;

require_once __DIR__ . '/../Support/DomQueries.php';

it('renders lightbox controls with dialog semantics and accessible names', function (): void {
    $wasBlazeEnabled = Blaze::isEnabled();
    Blaze::disable();

    try {
        $html = view('capell-theme-foundation::components.lightbox')->render();
    } finally {
        if ($wasBlazeEnabled) {
            Blaze::enable();
        }
    }
    expect(domQueryCount($html, '//*[@role="dialog"][@aria-modal="true"]'))->toBe(1);

    foreach (['close', 'previous', 'next'] as $label) {
        $name = __('capell-frontend::generic.' . $label);
        expect(domQueryCount($html, '//button[@aria-label="' . $name . '"]'))->toBe(1);
    }
});

it('opens media from keyboard events and restores focus through late startup', function (): void {
    $process = new Process(['node', __DIR__ . '/../Support/lightbox-outcomes.mjs']);
    $process->setTimeout(30);
    $process->run();
    expect($process->getExitCode())->toBe(0, $process->getOutput() . $process->getErrorOutput());
});
