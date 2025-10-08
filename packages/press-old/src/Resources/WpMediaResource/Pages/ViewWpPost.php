<?php

namespace Moox\Press\Resources\WpMediaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpMediaResource;

class ViewWpPost extends ViewRecord
{
    protected static string $resource = WpMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
