<?php

declare(strict_types=1);

namespace Moox\Support\SoftDelete\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Support\Models\MergesColumns;

trait HasSoftDelete
{
    use MergesColumns;
    use SoftDeletes;

    public function initializeHasSoftDelete(): void
    {
        $this->mergeColumns(
            [
                'deleted_at',
                'deleted_by_id',
                'deleted_by_type',
                'restored_at',
                'restored_by_id',
                'restored_by_type',
            ],
            [
                'deleted_at' => 'datetime',
                'deleted_by_id' => 'integer',
                'restored_at' => 'datetime',
                'restored_by_id' => 'integer',
            ],
        );
    }
}
