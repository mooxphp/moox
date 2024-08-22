<?php

namespace Moox\PressWiki\Resources\WpThemaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressWiki\Resources\WpThemaResource;

class EditWpThema extends EditRecord
{
    protected static string $resource = WpThemaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
