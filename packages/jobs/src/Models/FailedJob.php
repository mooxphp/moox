<?php

namespace Moox\Jobs\Models;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'failed_at'
    ];
}
