<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticDocumentTypeResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\DataLegacy\Filament\Resources\StaticDocumentTypeResource;

class CreateStaticDocumentType extends BaseCreateRecord
{
    protected static string $resource = StaticDocumentTypeResource::class;
}
