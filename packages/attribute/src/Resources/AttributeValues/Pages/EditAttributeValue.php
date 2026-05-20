<?php

namespace Moox\Attribute\Resources\AttributeValues\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Attribute\Resources\AttributeValuesResource;

class EditAttributeValue extends BaseEditDraft
{
    protected static string $resource = AttributeValuesResource::class;
    
    public function getHeading(): string
    {
        return $this->record->name;
    }
}
