<?php

namespace Moox\Press\Resources\WpCategoryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpCategoryResource;

class EditWpCategory extends EditRecord
{
    protected static string $resource = WpCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
