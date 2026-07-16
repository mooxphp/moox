<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticDocumentTypeResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Data\Filament\Resources\StaticDocumentTypeResource;

class CreateStaticDocumentType extends BaseCreateStaticRecord
{
    protected static string $resource = StaticDocumentTypeResource::class;
}
