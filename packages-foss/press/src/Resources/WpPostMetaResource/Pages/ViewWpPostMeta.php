<?php

namespace Moox\Press\Resources\WpPostMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpPostMetaResource;

class ViewWpPostMeta extends ViewRecord
{
    protected static string $resource = WpPostMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
