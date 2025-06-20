<?php

namespace Moox\UserSession\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserSession extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sessions';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'payload' => 'array',
        'last_activity' => 'integer',
        'whitelisted' => 'boolean',
    ];

    /**
     * Get the owning user model.
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the owning device model.
     */
    public function device(): MorphTo
    {
        return $this->morphTo();
    }
}
