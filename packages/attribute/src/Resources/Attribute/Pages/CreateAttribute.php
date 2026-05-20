<?php

namespace Moox\Attribute\Resources\Attribute\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\Attribute\Resources\AttributeResource;

class CreateAttribute extends BaseCreateDraft {
    protected static string $resource = AttributeResource::class;
}
