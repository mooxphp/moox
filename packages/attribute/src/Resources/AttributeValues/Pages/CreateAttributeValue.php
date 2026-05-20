<?php

namespace Moox\Attribute\Resources\AttributeValues\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\Attribute\Resources\AttributeValuesResource;

class CreateAttributeValue extends BaseCreateDraft {
    protected static string $resource = AttributeValuesResource::class;
}
