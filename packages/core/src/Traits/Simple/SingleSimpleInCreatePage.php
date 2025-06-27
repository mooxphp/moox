<?php

/**
 * @deprecated Use Base classes in Entities instead.
 */

declare(strict_types=1);

namespace Moox\Core\Traits\Simple;

trait SingleSimpleInCreatePage
{
    public function getFormActions(): array
    {
        return [];
    }
}
