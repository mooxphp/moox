<?php

namespace Moox\Skeleton\Models;

use Illuminate\Database\Eloquent\Model;

class Skeleton extends Model
{
    protected $table = 'skeleton';

    protected $fillable = [
        'name',
        'started_at',
        'finished_at',
        'failed',
    ];

    protected $casts = [
        'failed' => 'bool',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
