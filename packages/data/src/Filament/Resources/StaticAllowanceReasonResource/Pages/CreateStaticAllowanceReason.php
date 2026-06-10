<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticAllowanceReasonResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticAllowanceReasonResource;

class CreateStaticAllowanceReason extends BaseCreateRecord
{
    protected static string $resource = StaticAllowanceReasonResource::class;
}
