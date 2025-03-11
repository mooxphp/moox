<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\ResolveResourceClass;

abstract class BaseEditDraft extends EditRecord
{
    use ResolveResourceClass;

    protected function getFormActions(): array
    {
        return [];
    }
}
