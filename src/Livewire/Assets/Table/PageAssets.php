<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Livewire\Assets\Table;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Filament\Contracts\HasPageResource;
use Capell\Admin\Filament\Resources\Pages\Tables\PagesTable;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Override;

class PageAssets extends AbstractAssets implements HasPageResource
{
    public string $type = 'page';

    #[Locked]
    public string $tableConfiguration = PagesTable::class;

    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Page);
    }

    /**
     * @return Builder<Page>
     */
    public function getFilteredTableQuery(): Builder
    {
        $query = parent::getFilteredTableQuery();

        if (! $query instanceof Builder) {
            return Page::query()->whereRaw('1 = 0');
        }

        if (isset($this->getTableFilterState('filter')['language_id'])) {
            $language_id = $this->getTableFilterState('filter')['language_id'];
        } else {
            /** @var class-string<Language> $model */
            $model = Language::class;

            $language_id = $model::query()->default()->value('id');
        }

        $query->with([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language_id),
            'pageUrl' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language_id),
        ]);

        return $query;
    }

    /**
     * @return Builder<Page>
     */
    #[Override]
    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Core\Models\Page> $model */
        $model = Page::class;
        $pageId = $this->tableArguments['pageId'] ?? null;

        return $model::with([
            'translations.language',
            'ancestors.blueprint',
            'creator',
            'layout',
            'image',
            'media',
            'editor',
            'site.siteDomains',
            'blueprint',
        ])
            ->when(
                is_numeric($pageId),
                fn (BuilderContract $query): BuilderContract => $query->whereKeyNot((int) $pageId),
            )
            ->when(
                $this->existingRecords,
                fn (Builder $query) => $query->whereNotIn('id', $this->existingRecords),
            );
    }
}
