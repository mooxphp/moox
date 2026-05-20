<?php

namespace Moox\News\Resources\News\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\News\Resources\NewsResource;

class CreateNews extends BaseCreateDraft {  
    protected static string $resource = NewsResource::class;
}
