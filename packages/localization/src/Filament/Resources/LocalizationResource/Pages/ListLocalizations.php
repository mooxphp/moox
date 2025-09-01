<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Localization\Filament\Resources\LocalizationResource;

class ListLocalizations extends BaseListRecords
{
    protected static string $resource = LocalizationResource::class;
}
