<?php

namespace Moox\Press\Resources\WpPostMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpPostMetaResource;

class EditWpPostMeta extends EditRecord
{
    protected static string $resource = WpPostMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
