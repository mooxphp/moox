<?php

/**
 * @deprecated Use Base classes in Entities instead.
 */

declare(strict_types=1);

namespace Moox\Core\Traits\SoftDelete;

trait SingleSoftDeleteInEditPage
{
    public function getFormActions(): array
    {
        return [];
    }
}
