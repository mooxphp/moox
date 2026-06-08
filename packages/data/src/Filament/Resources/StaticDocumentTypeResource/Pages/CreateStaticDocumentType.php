<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticDocumentTypeResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticDocumentTypeResource;

class CreateStaticDocumentType extends BaseCreateRecord
{
    protected static string $resource = StaticDocumentTypeResource::class;
}
