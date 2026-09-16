<?php

declare(strict_types=1);

namespace Moox\Mjml\Enums;

enum ValidationLevel: string
{
    case Strict = 'strict';
    case Soft = 'soft';
    case Skip = 'skip';
}
