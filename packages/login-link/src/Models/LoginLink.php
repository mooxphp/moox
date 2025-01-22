<?php

namespace Moox\LoginLink\Models;

use Override;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LoginLink extends Model
{
    protected $table = 'login_links';

    protected $fillable = [
        'user_type',
        'user_id',
        'email',
        'token',
        'expires_at',
        'used_at',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // TODO: Add the appends here.
        // $this->appends = [
        // ];
    }

    #[Override]
    protected static function boot()
    {
        parent::boot();

        LoginLink::creating(function ($item): void {
            if (empty($item->token)) {
                $item->token = Str::random(40);
            }
        });
    }

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
}
