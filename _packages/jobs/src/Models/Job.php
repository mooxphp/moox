<?php

namespace Adrolli\FilamentJobManager\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'jobs';

    protected $fillable = [
        'reserved_at',
    ];

    /*
     *--------------------------------------------------------------------------
     * Mutators
     *--------------------------------------------------------------------------
     */
    public function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->reserved_at) {
                    return 'running';
                } else {
                    return 'waiting';
                }
            },
        );
    }

    /*
     *--------------------------------------------------------------------------
     * Methods
     *--------------------------------------------------------------------------
     */

    public function getDisplayNameAttribute()
    {
        $payload = json_decode($this->attributes['payload'], true);

        return $payload['displayName'] ?? null;
    }
}
