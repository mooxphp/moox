<?php

namespace Moox\Core\Entities\Items\Record\Enums;

enum RecordStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';
}
