<?php

namespace Moox\Page\Resources\PageResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\Page\Resources\PageResource;

class CreatePage extends BaseCreateDraft
{
    protected static string $resource = PageResource::class;
}
