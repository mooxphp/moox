<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseEditItem extends EditRecord
{
    use CanResolveResourceClass;

    protected function getFormActions(): array
    {
        return [];
    }
}
