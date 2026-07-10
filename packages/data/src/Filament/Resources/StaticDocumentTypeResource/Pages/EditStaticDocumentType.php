<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticDocumentTypeResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseEditStaticRecord;
use Moox\Data\Filament\Resources\StaticDocumentTypeResource;

class EditStaticDocumentType extends BaseEditStaticRecord
{
    protected static string $resource = StaticDocumentTypeResource::class;
}
