<?php

declare(strict_types=1);

namespace Moox\User\Resources\UserResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseViewRecord;
use Moox\User\Resources\UserResource;

class ViewUser extends BaseViewRecord
{
    protected static string $resource = UserResource::class;
}
