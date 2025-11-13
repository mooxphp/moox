<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\Tag\Resources\TagResource;

class CreateTag extends BaseCreateDraft
{
    protected static string $resource = TagResource::class;
}
