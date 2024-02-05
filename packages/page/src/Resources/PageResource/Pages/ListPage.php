<?php

namespace Moox\Page\Resources\PageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Page\Models\Page;
use Moox\Page\Resources\PageResource;
use Moox\Page\Resources\PageResource\Widgets\PageWidgets;

class ListPage extends ListRecords
{
    public static string $resource = PageResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            PageWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('page::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Page {
                    return $model::create($data);
                }),
        ];
    }
}
