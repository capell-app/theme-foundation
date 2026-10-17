<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Support;

use Closure;
use Illuminate\View\Component;
use Override;

// A namespaced component lets Blade resolve this public extension boundary as a class.
final class FooterContentFixture extends Component
{
    public function __construct(public string $area) {}

    #[Override]
    public function render(): Closure
    {
        return fn (): string => $this->area === 'footer' ? '<span>Installed footer widget content</span>' : '';
    }
}
