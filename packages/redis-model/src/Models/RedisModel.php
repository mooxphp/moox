<?php

namespace Moox\RedisModel\Models;

use Illuminate\Database\Eloquent\Model;

class RedisModel extends Model
{
    protected $table = 'redis_models';

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
