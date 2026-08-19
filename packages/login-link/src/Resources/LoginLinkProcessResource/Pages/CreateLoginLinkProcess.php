<?php

declare(strict_types=1);

namespace Moox\LoginLink\Resources\LoginLinkProcessResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\LoginLink\Resources\LoginLinkProcessResource;

class CreateLoginLinkProcess extends BaseCreateRecord
{
    protected static string $resource = LoginLinkProcessResource::class;
}
