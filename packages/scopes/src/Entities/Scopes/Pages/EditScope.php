<?php

declare(strict_types=1);

namespace Moox\Scopes\Entities\Scopes\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseEditItem;
use Moox\Scopes\Entities\Scopes\ScopeResource;

class EditScope extends BaseEditItem
{
    protected static string $resource = ScopeResource::class;
}
