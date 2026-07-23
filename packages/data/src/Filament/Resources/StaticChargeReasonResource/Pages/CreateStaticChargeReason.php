<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticChargeReasonResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Data\Filament\Resources\StaticChargeReasonResource;

class CreateStaticChargeReason extends BaseCreateStaticRecord
{
    protected static string $resource = StaticChargeReasonResource::class;
}
