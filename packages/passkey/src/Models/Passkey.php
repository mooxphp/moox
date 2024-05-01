<?php

namespace Moox\Passkey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passkey extends Model
{
    protected $table = 'passkeys';

    protected $fillable = [
        'title',
        'user_id',
        'user_type',
        'credential_id',
        'public_key',
        'device_id',
        'session_id',
    ];

    protected $casts = [
        'session_id' => 'string',
        'credential_id' => 'encrypted',
        'public_key' => 'encrypted:json',
    ];

    /**
     * Get the owning user model.
     *
     * TODO: Doing a fallback to the User model for now, but this is not ideal.
     */
    public function user(): BelongsTo
    {
        if (isset($this->user_type)) {
            return $this->belongsTo($this->user_type, 'user_id');
        } else {
            return $this->belongsTo('App\Models\User', 'user_id');
        }
    }

    /**
     * Get the associated device.
     */
    public function userDevice(): BelongsTo
    {
        return $this->belongsTo('Moox\UserDevice\Models\UserDevice', 'device_id');
    }

    /**
     * Get the associated session.
     */
    public function userSession(): BelongsTo
    {
        return $this->belongsTo('Moox\UserSession\Models\UserSession', 'session_id');
    }
}
