<?php

namespace Moox\Core\Entities\Items\Record\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseEditRecord extends EditRecord
{
    use CanResolveResourceClass;
}
