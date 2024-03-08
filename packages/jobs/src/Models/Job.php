<?php

namespace Moox\Jobs\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'jobs';

    protected $fillable = [
        'reserved_at',
    ];

    public function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->reserved_at) {
                    return 'running';
                    }

                return 'waiting';
            },
        );
    }

    public function getDisplayNameAttribute()
    {
        $payload = json_decode($this->attributes['payload'], true);

        return $payload['displayName'] ?? null;
    }
}
