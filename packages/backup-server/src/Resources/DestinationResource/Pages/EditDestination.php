<?php

namespace Moox\BackupServerUi\Resources\DestinationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\BackupServerUi\Resources\DestinationResource;

class EditDestination extends EditRecord
{
    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    public function getTitle(): string
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return 'Edit '.$this->getRecordTitle().' destination';
    }
}
