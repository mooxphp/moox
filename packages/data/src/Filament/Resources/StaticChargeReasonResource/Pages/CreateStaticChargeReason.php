<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticChargeReasonResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticChargeReasonResource;

class CreateStaticChargeReason extends BaseCreateRecord
{
    protected static string $resource = StaticChargeReasonResource::class;
}
