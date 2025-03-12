<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseEditDraft extends EditRecord
{
    use CanResolveResourceClass;

    protected function getFormActions(): array
    {
        return [];
    }
}
