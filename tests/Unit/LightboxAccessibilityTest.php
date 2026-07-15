<?php

declare(strict_types=1);

it('renders lightbox controls with dialog semantics and accessible names', function (): void {
    $lightbox = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/lightbox.blade.php');

    expect($lightbox)->toContain('role="dialog"')
        ->and($lightbox)->toContain('aria-modal="true"')
        ->and($lightbox)->toContain('x-ref="lightboxDialog"')
        ->and($lightbox)->toContain("aria-label=\"{{ __('capell-frontend::generic.close') }}\"")
        ->and($lightbox)->toContain("aria-label=\"{{ __('capell-frontend::generic.previous') }}\"")
        ->and($lightbox)->toContain("aria-label=\"{{ __('capell-frontend::generic.next') }}\"");
});

it('opens lightbox media from keyboard events and restores focus on close', function (): void {
    $lightboxScript = file_get_contents(dirname(__DIR__, 2) . '/resources/js/utilities/lightbox.js');

    expect($lightboxScript)->toContain("document.addEventListener('keydown'")
        ->and($lightboxScript)->toContain("['Enter', ' '].includes(event.key)")
        ->and($lightboxScript)->toContain('openLightboxFromElement(element)')
        ->and($lightboxScript)->toContain('this.previousFocus = document.activeElement')
        ->and($lightboxScript)->toContain('this.previousFocus?.focus?.()');
});

it('registers the lightbox before Alpine scan and recovers late-loaded viewers', function (): void {
    $lightboxScript = file_get_contents(dirname(__DIR__, 2) . '/resources/js/utilities/lightbox.js');

    expect($lightboxScript)->toContain("document.addEventListener('alpine:init', initialize)")
        ->and($lightboxScript)->toContain("document.addEventListener('livewire:init', initialize)")
        ->and($lightboxScript)->toContain('const componentState = root._x_dataStack?.[0]')
        ->and($lightboxScript)->toContain('Alpine.destroyTree(root)')
        ->and($lightboxScript)->toContain('Alpine.initTree(root)');
});
