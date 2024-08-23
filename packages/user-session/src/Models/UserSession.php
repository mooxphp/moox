<?php

namespace Moox\UserSession\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\UserDevice\Models\UserDevice;

class UserSession extends Model
{
    protected $table = 'sessions';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_type',
        'user_id',
        'device_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
        'whitelisted',
    ];

    protected $casts = [
        'id' => 'string',
        'payload' => 'array',
        'last_activity' => 'integer',
        'whitelisted' => 'boolean',
    ];

    public function user()
    {
        return $this->morphTo();
    }

    public function device()
    {
        return $this->morphTo();
    }
}
