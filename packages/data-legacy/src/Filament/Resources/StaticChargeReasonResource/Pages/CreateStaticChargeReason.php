<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticChargeReasonResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\DataLegacy\Filament\Resources\StaticChargeReasonResource;

class CreateStaticChargeReason extends BaseCreateRecord
{
    protected static string $resource = StaticChargeReasonResource::class;
}
