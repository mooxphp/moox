<?php

namespace Moox\Locate\Models;

use Illuminate\Database\Eloquent\Model;

class Locate extends Model
{
    protected $table = 'locates';

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
