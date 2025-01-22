<?php

namespace Moox\Passkey\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserSession\Models\UserSession;

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
        if (property_exists($this, 'user_type') && $this->user_type !== null) {
            return $this->belongsTo($this->user_type, 'user_id');
        } else {
            return $this->belongsTo(User::class, 'user_id');
        }
    }

    /**
     * Get the associated device.
     */
    public function userDevice(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    /**
     * Get the associated session.
     */
    public function userSession(): BelongsTo
    {
        return $this->belongsTo(UserSession::class, 'session_id');
    }
}
