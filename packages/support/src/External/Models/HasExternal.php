<?php

declare(strict_types=1);

namespace Moox\Support\External\Models;

use Moox\Support\Models\MergesColumns;

trait HasExternal
{
    use MergesColumns;

    public function initializeHasExternal(): void
    {
        $this->mergeColumns([
            'external_reference',
            'external_info',
            'external_status',
        ]);
    }

    public function hasExternalReference(): bool
    {
        return filled($this->external_reference);
    }

    public function hasExternalInfo(): bool
    {
        return filled($this->external_info);
    }

    public function hasExternalStatus(): bool
    {
        return filled($this->external_status);
    }
}