<?php

namespace Moox\Devops\Models;

use Illuminate\Database\Eloquent\Model;

class MooxServer extends Model
{
    protected $table = 'moox_servers';

    protected $fillable = [
        'name',
        'forge_id',
        'ip_address',
        'type',
        'provider',
        'region',
        'ubuntu_version',
        'db_status',
        'redis_status',
        'php_version',
        'is_ready',
    ];

    protected $casts = [
        'is_ready' => 'bool',
    ];

    public function projects()
    {
        return $this->hasMany(MooxProject::class, 'server_id', 'forge_id');
    }
}
