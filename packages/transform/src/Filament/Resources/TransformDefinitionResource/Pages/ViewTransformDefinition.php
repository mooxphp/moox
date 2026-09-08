<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;
use Moox\Transform\Filament\Resources\TransformDefinitionResource;

class ViewTransformDefinition extends BaseViewRecord
{
    protected static string $resource = TransformDefinitionResource::class;
}
