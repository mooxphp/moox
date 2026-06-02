<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;
use Moox\Transform\Filament\Resources\TransformDefinitionResource;

class EditTransformDefinition extends EditRecord
{
    use BaseInEditPage;
    use SingleSimpleInEditPage;

    protected static string $resource = TransformDefinitionResource::class;
}
