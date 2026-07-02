<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\App;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Body extends Component
{
    public function __construct(
        public Layout $layout,
        public mixed $language,
        public mixed $pageRecord,
        public Site $site,
        public Theme $theme,
        public ?string $bodyClass = null,
    ) {}

    public function render(): View
    {
        return view('capell-theme-foundation::components.app.body');
    }
}
