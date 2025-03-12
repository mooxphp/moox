<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;

class TestItem extends Model
{
    protected $table = 'preview_test_items';

    protected $fillable = [
        'light',
        'title',
        'content',
        'street',
        'city',
        'postal_code',
        'country',
        'status',
        'type',
    ];

    protected $casts = [
    ];
}
