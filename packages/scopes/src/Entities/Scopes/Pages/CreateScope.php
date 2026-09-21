<?php

declare(strict_types=1);

namespace Moox\Scopes\Entities\Scopes\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseCreateItem;
use Moox\Scopes\Entities\Scopes\ScopeResource;

class CreateScope extends BaseCreateItem
{
    protected static string $resource = ScopeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        ScopeResource::assertCreatePayloadIsAllowed($data);

        return $data;
    }
}
