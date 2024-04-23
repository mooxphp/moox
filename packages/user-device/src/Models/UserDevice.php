<?php

namespace Moox\UserDevice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserDevice extends Model
{
    protected $table = 'user_devices';

    protected $fillable = [
        'title', 'slug', 'user_id', 'user_type', 'user_agent',
        'os', 'browser', 'country', 'location', 'whitelisted', 'active', 'ip_address'
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate a slug when creating a new device.
        static::creating(function ($device) {
            $device->slug = Str::slug($device->title);
        });
    }

    /**
     * Get the owning user model.
     */
    public function user()
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include active devices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include whitelisted devices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhitelisted($query)
    {
        return $query->where('whitelisted', true);
    }
}
