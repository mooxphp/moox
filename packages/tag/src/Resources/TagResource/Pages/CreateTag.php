<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Moox\Tag\Resources\TagResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;

class CreateTag extends BaseCreateDraft
{
    protected static string $resource = TagResource::class;
}
