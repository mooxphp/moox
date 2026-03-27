<?php

namespace Moox\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Support\Scopes\ScopeValue;

class Scope extends Model
{
    protected $table = 'scopes';

    protected $fillable = [
        'scope',
        'label',
        'origin',
        'source',
        'context',
        'boundary',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getScopeObject(): ?ScopeValue
    {
        return ScopeValue::parse($this->scope);
    }
}

