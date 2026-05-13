<?php

namespace Moox\Attribute\Moox\Entities\AttributeValues\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;

class EditAttributeValue extends BaseEditDraft
{
    public function getHeading(): string
    {
        return $this->record->name;
    }
}
