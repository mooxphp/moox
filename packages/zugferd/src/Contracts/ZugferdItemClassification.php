<?php

declare(strict_types=1);

namespace Moox\Zugferd\Contracts;

interface ZugferdItemClassification
{
    public string $code { get; }

    public string $schemeId { get; }

    public ?string $schemeVersionId { get; }
}
