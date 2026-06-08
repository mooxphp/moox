<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticVatExemptionReasonResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticVatExemptionReasonResource;

class CreateStaticVatExemptionReason extends BaseCreateRecord
{
    protected static string $resource = StaticVatExemptionReasonResource::class;
}
