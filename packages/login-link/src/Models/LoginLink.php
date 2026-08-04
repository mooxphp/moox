<?php

namespace Moox\LoginLink\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;

class LoginLink extends Model
{
    protected $table = 'login_links';

    protected $attributes = [
        'process' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
    ];

    protected $fillable = [
        'panel_id',
        'process',
        'user_type',
        'user_id',
        'subject_type',
        'subject_id',
        'email',
        'expires_at',
        'used_at',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Legacy authenticatable morph retained for backwards compatibility.
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Polymorphic subject of the link process (authenticatable for login; arbitrary for others).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
