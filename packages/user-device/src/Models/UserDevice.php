<?php

namespace Moox\UserDevice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class UserDevice extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'user_id',
        'user_type',
        'user_agent',
        'platform',
        'os',
        'browser',
        'city',
        'country',
        'location',
        'whitelisted',
        'active',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'bool',
        'whitelisted' => 'bool',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        UserDevice::creating(function ($item) {
            $baseSlug = Str::slug($item->title);
            $slug = $baseSlug;
            $counter = 1;

            while (UserDevice::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $item->slug = $slug;
        });
    }

    /**
     * Get the owning user model.
     */
    public function user(): MorphTo
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
