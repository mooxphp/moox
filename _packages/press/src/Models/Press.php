<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Model;

class Press extends Model
{
    protected $table = 'press';

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
