<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseListItems extends ListRecords
{
    use CanResolveResourceClass;

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
