<?php

namespace Moox\News\Resources\News\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Moox\News\Resources\NewsResource;

class ViewNews extends BaseViewDraft
{
    protected static string $resource = NewsResource::class;
}
