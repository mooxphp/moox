<?php

declare(strict_types=1);

namespace Moox\LoginLink\Resources\LoginLinkProcessResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\LoginLink\Resources\LoginLinkProcessResource;

class EditLoginLinkProcess extends BaseEditRecord
{
    protected static string $resource = LoginLinkProcessResource::class;
}
