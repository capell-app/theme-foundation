<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\View\Components\Widget\Page;

use Capell\FoundationTheme\View\Components\Widget\AbstractWidget;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Override;

abstract class AbstractPagesWidget extends AbstractWidget
{
    public ?string $componentItem = null;

    /**
     * @var Collection<array-key, mixed>|LengthAwarePaginator<array-key, mixed>|null
     */
    public Collection|LengthAwarePaginator|null $pages = null;

    protected static string $defaultView = 'capell-theme-foundation::components.widget.asset.pages';

    #[Override]
    public function render(array $data = []): View|string|Closure
    {
        if ($this->skipRender && config('capell-layout-builder.widget.skip_render_empty', true) === true) {
            return '';
        }

        return parent::render([
            ...$data,
            'pages' => $this->pages ?? collect(),
        ]);
    }

    protected function paginationEnabled(): bool
    {
        return (bool) ($this->widget->meta['pagination'] ?? false);
    }

    protected function paginationLimit(): ?int
    {
        $limit = $this->widget->meta['limit'] ?? null;

        return is_numeric($limit) ? (int) $limit : null;
    }

    protected function paginationPage(string $paginationKey): ?int
    {
        if (! $this->paginationEnabled()) {
            return null;
        }

        $page = Request::integer($paginationKey, Request::integer('page', 1));

        return max(1, $page);
    }
}
