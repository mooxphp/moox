<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticCurrencyResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\DataLegacy\Filament\Resources\StaticCurrencyResource;

class CreateStaticCurrency extends BaseCreateRecord
{
    protected static string $resource = StaticCurrencyResource::class;
}
