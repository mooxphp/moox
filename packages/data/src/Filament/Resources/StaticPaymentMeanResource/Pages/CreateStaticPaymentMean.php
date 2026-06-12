<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticPaymentMeanResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticPaymentMeanResource;

class CreateStaticPaymentMean extends BaseCreateRecord
{
    protected static string $resource = StaticPaymentMeanResource::class;
}
