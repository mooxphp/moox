<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\ViewRecord;

abstract class ItemViewPage extends ViewRecord
{
    public function getFormActions(): array
    {
        return [];
    }
}
