<?php

declare(strict_types=1);

namespace Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Transform\Filament\Resources\TransformDefinitionResource;
use Moox\Transform\Models\TransformDefinition;

class ListTransformDefinitions extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = TransformDefinitionResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('transform-definition.tabs', TransformDefinition::class);
    }
}
