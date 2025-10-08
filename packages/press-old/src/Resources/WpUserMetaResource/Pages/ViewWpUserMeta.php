<?php

namespace Moox\Press\Resources\WpUserMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpUserMetaResource;

class ViewWpUserMeta extends ViewRecord
{
    protected static string $resource = WpUserMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
