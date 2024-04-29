<?php

namespace Moox\UserSession\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\UserDevice\Models\UserDevice;

class UserSession extends Model
{
    protected $table = 'sessions';

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

    /**
     * Get the owning user model.
     *
     * TODO:
     * By setting the current model as the user_type, we can have a smart start.
     */
    public function user()
    {
        return $this->belongsTo($this->user_type, 'user_id');
    }

    /**
     * Get the owning device model.
     *
     * TODO:
     * This should be dynamic based on the configured device model.
     */
    public function device()
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }
}
