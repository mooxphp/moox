<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

enum TestStatusEnum: string
{
    case Draft = 'draft';
    case Published = 'published';
}
