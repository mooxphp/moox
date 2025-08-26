<?php

namespace Moox\Record\Enums;

enum RecordStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';
}
