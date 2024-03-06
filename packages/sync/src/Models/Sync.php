<?php

namespace Moox\Sync\Models;

use Illuminate\Database\Eloquent\Model;

class Sync extends Model
{
    protected $table = 'sync';

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
