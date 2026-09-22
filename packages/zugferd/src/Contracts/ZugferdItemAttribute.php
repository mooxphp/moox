<?php

declare(strict_types=1);

namespace Moox\Zugferd\Contracts;

interface ZugferdItemAttribute
{
    public string $name { get; }

    public string $value { get; }
}
