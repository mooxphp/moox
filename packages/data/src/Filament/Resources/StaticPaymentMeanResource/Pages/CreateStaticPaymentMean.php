<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticPaymentMeanResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Data\Filament\Resources\StaticPaymentMeanResource;

class CreateStaticPaymentMean extends BaseCreateStaticRecord
{
    protected static string $resource = StaticPaymentMeanResource::class;
}
