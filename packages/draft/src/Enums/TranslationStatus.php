<?php

namespace Moox\Draft\Enums;

enum TranslationStatus: string
{
    case DRAFT = 'draft';
    case WAITING = 'waiting';
    case PRIVATE = 'private';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case NOT_TRANSLATED = 'not translated';
    case DELETED = 'deleted';
}
