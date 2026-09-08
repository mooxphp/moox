<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Transform\Filament\Resources\TransformDefinitionResource;

class EditTransformDefinition extends BaseEditRecord
{
    protected static string $resource = TransformDefinitionResource::class;
}
