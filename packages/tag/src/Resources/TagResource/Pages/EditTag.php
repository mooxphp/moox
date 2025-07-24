<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Tag\Resources\TagResource;

class EditTag extends BaseEditDraft
{
    protected static string $resource = TagResource::class;
}
