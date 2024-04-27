<?php

namespace Moox\LoginLink\Models;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        LoginLink::creating(function ($item) {
            if (empty($item->token)) {
                $item->token = Str::random(40);
            }
        });
    }

    /**
     * Get the owning user model.
     */
    public function user()
    {
        if (isset($this->user_type)) {
            return $this->belongsTo($this->user_type, 'user_id');
        } else {
            return $this->belongsTo('App\Models\User', 'user_id');
        }
    }
}
