<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\ViewRecord;

abstract class MooxViewPage extends ViewRecord
{
    public function getFormActions(): array
    {
        return [];
    }
}
