<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Localization\Filament\Resources\LocalizationResource;

class CreateLocalization extends BaseCreateRecord
{
    protected static string $resource = LocalizationResource::class;
}
