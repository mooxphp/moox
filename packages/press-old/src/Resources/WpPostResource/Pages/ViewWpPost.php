<?php

namespace Moox\Press\Resources\WpPostResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpPostResource;

class ViewWpPost extends ViewRecord
{
    protected static string $resource = WpPostResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
