<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\ResolveResourceClass;

abstract class BaseViewDraft extends ViewRecord
{
    use ResolveResourceClass;

    public function getFormActions(): array
    {
        return [];
    }
}
