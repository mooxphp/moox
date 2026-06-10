<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticDocumentTypeResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\DataLegacy\Filament\Resources\StaticDocumentTypeResource;

class EditStaticDocumentType extends BaseEditRecord
{
    protected static string $resource = StaticDocumentTypeResource::class;
}
