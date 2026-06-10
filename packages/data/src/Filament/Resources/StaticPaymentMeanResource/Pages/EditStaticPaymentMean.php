<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticPaymentMeanResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Data\Filament\Resources\StaticPaymentMeanResource;

class EditStaticPaymentMean extends BaseEditRecord
{
    protected static string $resource = StaticPaymentMeanResource::class;
}
