<?php

declare(strict_types=1);

namespace Moox\Scopes\Entities\Scopes\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Scopes\Entities\Scopes\ScopeResource;

class ListScopes extends BaseListItems
{
    protected static string $resource = ScopeResource::class;
}
