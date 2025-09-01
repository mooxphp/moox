<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Localization\Filament\Resources\LocalizationResource;

class EditLocalization extends BaseEditRecord
{
    protected static string $resource = LocalizationResource::class;
}
