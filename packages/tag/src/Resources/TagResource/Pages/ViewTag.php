<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Moox\Tag\Resources\TagResource;

class ViewTag extends BaseViewDraft
{
    protected static string $resource = TagResource::class;
}
