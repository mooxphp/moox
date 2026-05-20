<?php

namespace Moox\Attribute\Resources\Attribute\Pages;

use Moox\Attribute\Resources\AttributeResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;

class EditAttribute extends BaseEditDraft
{
    protected static string $resource = AttributeResource::class;

    public function getHeading(): string
    {
        return $this->record->name;
    }
}
