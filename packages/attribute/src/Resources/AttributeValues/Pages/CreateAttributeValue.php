<?php

namespace Moox\Attribute\Resources\AttributeValues\Pages;

use Moox\Attribute\Resources\AttributeValuesResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;

class CreateAttributeValue extends BaseCreateDraft
{
    protected static string $resource = AttributeValuesResource::class;
}
