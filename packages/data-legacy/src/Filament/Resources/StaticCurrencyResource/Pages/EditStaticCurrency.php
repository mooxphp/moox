<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticCurrencyResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\DataLegacy\Filament\Resources\StaticCurrencyResource;

class EditStaticCurrency extends BaseEditRecord
{
    protected static string $resource = StaticCurrencyResource::class;
}
