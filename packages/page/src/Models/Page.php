<?php

namespace Moox\Page\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use \Moox\RedisModel\UsesRedis;

    public $useRedis = true;

    protected $table = 'page';

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
