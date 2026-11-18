<?php

declare(strict_types=1);

namespace Moox\Media\Support;

enum MediaUploadStatus: string
{
    case Created = 'created';
    case Duplicate = 'duplicate';
    case Replaced = 'replaced';
    case Edited = 'edited';
}
