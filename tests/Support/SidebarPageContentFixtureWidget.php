<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Tests\Support;

use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;
use Livewire\Component;

final class SidebarPageContentFixtureWidget extends Component
{
    public string $widgetReference;

    public string $copy;

    public function mount(string $widgetReference): void
    {
        $this->widgetReference = $widgetReference;
        $copy = data_get(OpaqueWidgetReference::decode($widgetReference), 'widget_data.meta.preview_copy');
        $this->copy = is_string($copy) ? $copy : '';
    }

    public function render(): string
    {
        return '<div>' . e($this->copy) . '</div>';
    }
}
