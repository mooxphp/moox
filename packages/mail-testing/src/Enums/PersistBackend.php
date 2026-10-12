<?php

declare(strict_types=1);

namespace Moox\MailTesting\Enums;

enum PersistBackend: string
{
    case Storage = 'storage';
    case Database = 'database';
}
