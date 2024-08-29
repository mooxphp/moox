<?php

namespace Moox\Sync\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'domain',
        'ip_address',
        'show_in_menu',
        'order',
        'read_only',
        'locked',
        'lock_reason',
        'master',
        'thumbnail',
        'api_token',
    ];

    protected $searchableFields = ['*'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'selection' => 'boolean',
        'read_only' => 'boolean',
        'locked' => 'boolean',
        'master' => 'boolean',
    ];

    public function sourcePlatform(): HasMany
    {
        return $this->hasMany(Sync::class, 'source_platform_id');
    }

    public function targetPlatform(): HasMany
    {
        return $this->hasMany(Sync::class, 'target_platform_id');
    }

    public function syncs()
    {
        return $this->sources()->union($this->targets());
    }

    public function platformable()
    {
        return $this->morphTo();
    }

    /* TODO: this model must be dynamic, not user
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_platform');
    }
    */
}
