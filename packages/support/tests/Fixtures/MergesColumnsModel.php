<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Models\MergesColumns;

class MergesColumnsModel extends Model
{
    use MergesColumns;

    protected $table = 'merges_columns_models';

    protected $fillable = ['name'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    public function mergeTestColumns(): void
    {
        $this->mergeColumns(
            ['name', 'email'],
            [
                'enabled' => 'boolean',
                'data' => 'array',
            ],
            [
                'status' => 'active',
                'type' => 'default',
            ],
        );
    }
}