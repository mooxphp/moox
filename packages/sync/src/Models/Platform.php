<?php

namespace Moox\Sync\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'show_in_menu',
        'order',
        'read_only',
        'locked',
        'master',
        'thumbnail',
        'api_token',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'selection' => 'boolean',
        'read_only' => 'boolean',
        'locked' => 'boolean',
        'master' => 'boolean',
    ];

    public function sources()
    {
        return $this->hasMany(Sync::class, 'source_platform_id');
    }

    public function targets()
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
