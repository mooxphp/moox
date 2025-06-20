<?php

namespace Moox\BackupServerUi\Resources\SourceResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\BackupServerUi\Resources\SourceResource;

class ListSources extends ListRecords
{
    protected static string $resource = SourceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
