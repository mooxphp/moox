<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Support\Str;

final class CorrelationIdGenerator
{
    public function mint(): string
    {
        return (string) Str::uuid();
    }
}
