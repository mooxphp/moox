<?php

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Model;

class Builder extends Model
{
    protected $table = 'builder';

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
