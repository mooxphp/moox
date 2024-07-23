<?php

namespace Moox\Press\Resources\WpCategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpCategoryResource;

class ListWpCategories extends ListRecords
{
    protected static string $resource = WpCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
