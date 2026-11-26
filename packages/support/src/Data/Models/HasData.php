<?php

declare(strict_types=1);

namespace Moox\Support\Data\Models;

use Moox\Support\Models\MergesColumns;

trait HasData
{
    use MergesColumns;

    public function initializeHasData(): void
    {
        $this->mergeColumns(
            ['data'],
            ['data' => 'array'],
        );
    }
}