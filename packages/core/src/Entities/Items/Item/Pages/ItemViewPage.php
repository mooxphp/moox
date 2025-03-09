<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\ResolveResourceClass;

abstract class ItemViewPage extends ViewRecord
{
    use ResolveResourceClass;

    public function getFormActions(): array
    {
        return [];
    }
}
