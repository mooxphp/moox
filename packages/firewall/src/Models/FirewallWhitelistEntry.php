<?php

namespace Moox\Firewall\Models;

use Illuminate\Database\Eloquent\Model;

class FirewallWhitelistEntry extends Model
{
    protected $fillable = [
        'ip_address',
        'label',
        'is_active',
        'allow_all_routes',
        'allowed_routes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allow_all_routes' => 'boolean',
            'allowed_routes' => 'array',
        ];
    }
}
