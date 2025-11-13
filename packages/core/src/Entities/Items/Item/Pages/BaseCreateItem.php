<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseCreateItem extends CreateRecord
{
    use CanResolveResourceClass;

    public function getFormActions(): array
    {
        return [

        ];
    }
}
