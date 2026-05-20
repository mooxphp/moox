<?php

namespace Moox\Attribute\Resources\Attribute\Pages;

use Moox\Attribute\Resources\AttributeResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;

class CreateAttribute extends BaseCreateDraft
{
    protected static string $resource = AttributeResource::class;
}
