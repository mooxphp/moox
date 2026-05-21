<?php

namespace Moox\LoginLink\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoginLink extends Model
{
    protected $table = 'login_links';

    protected $fillable = [
        'panel_id',
        'user_type',
        'user_id',
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
     * Get the owning user model.
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
