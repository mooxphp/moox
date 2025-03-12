<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseViewDraft extends ViewRecord
{
    use CanResolveResourceClass;

    public function getFormActions(): array
    {
        return [];
    }
}
