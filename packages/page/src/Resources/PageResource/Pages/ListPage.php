<?php

namespace Moox\Page\Resources\PageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Page\Models\Page;
use Moox\Page\Resources\PageResource;
use Moox\Page\Resources\PageResource\Widgets\PageWidgets;
use Override;

class ListPage extends ListRecords
{
    public static string $resource = PageResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PageWidgets::class,
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('page::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): Page => $model::create($data)),
        ];
    }
}
