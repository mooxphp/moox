<?php

namespace Moox\Locate\Resources\AreaResource\Pages;

use Override;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Locate\Resources\AreaResource;

class ListPage extends ListRecords
{
    public static string $resource = AreaResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]protected function getHeaderWidgets(): array
    {
        return [
            // LocateWidgets::class,
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('locate::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn(array $data, string $model): static => $model::create($data)),
        ];
    }
}
