<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Transform\Filament\Resources\TransformDefinitionResource;

class CreateTransformDefinition extends BaseCreateRecord
{
    protected static string $resource = TransformDefinitionResource::class;
}
