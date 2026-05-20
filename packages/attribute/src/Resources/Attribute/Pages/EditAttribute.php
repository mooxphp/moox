<?php

namespace Moox\Attribute\Resources\Attribute\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Attribute\Resources\AttributeResource;

class EditAttribute extends BaseEditDraft
{
    protected static string $resource = AttributeResource::class;
    public function getHeading(): string
    {
        return $this->record->name;
    }
}
