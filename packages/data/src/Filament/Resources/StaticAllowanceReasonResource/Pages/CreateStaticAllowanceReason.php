<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticAllowanceReasonResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Data\Filament\Resources\StaticAllowanceReasonResource;

class CreateStaticAllowanceReason extends BaseCreateStaticRecord
{
    protected static string $resource = StaticAllowanceReasonResource::class;
}
