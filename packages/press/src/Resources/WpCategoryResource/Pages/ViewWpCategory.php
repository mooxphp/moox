<?php

namespace Moox\Press\Resources\WpCategoryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpCategoryResource;

class ViewWpCategory extends ViewRecord
{
    protected static string $resource = WpCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
