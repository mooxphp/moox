<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticVatExemptionReasonResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Data\Filament\Resources\StaticVatExemptionReasonResource;

class CreateStaticVatExemptionReason extends BaseCreateStaticRecord
{
    protected static string $resource = StaticVatExemptionReasonResource::class;
}
