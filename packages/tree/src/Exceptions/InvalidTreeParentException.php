<?php

declare(strict_types=1);

namespace Moox\Tree\Exceptions;

use RuntimeException;

final class InvalidTreeParentException extends RuntimeException
{
    public static function selfParent(): self
    {
        return new self('Ein Eintrag kann nicht sein eigener Elterneintrag sein.');
    }

    public static function descendantAsParent(): self
    {
        return new self('Ein Eintrag kann nicht unter einem eigenen Kind verschoben werden.');
    }

    public static function moveBlocked(): self
    {
        return new self('Ein Eintrag kann nicht unter sich selbst oder einem eigenen Kind liegen.');
    }
}
