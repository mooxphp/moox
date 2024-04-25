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
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        LoginLink::creating(function ($item) {
            $baseSlug = Str::slug($item->title);
            $slug = $baseSlug;
            $counter = 1;

            while (LoginLink::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $item->slug = $slug;
        });
    }

    /**
     * Get the owning user model.
     */
    public function user()
    {
        return $this->belongsTo($this->user_type, 'user_id');
    }
}
