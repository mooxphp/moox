<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ApiEndpoint extends Model
{
    protected $fillable = [
        'name',
        'api_connection_id',
        'path',
        'method',
        'direct_access',
        'variables',
        'response_map',
        'expected_response',
        'field_mappings',
        'transformers',
        'lang_override',
        'rate_limit',
        'rate_window',
        'status',
        'timeout',
    ];

    protected $casts = [
        'direct_access' => 'boolean',
        'variables' => 'array',
        'response_map' => 'array',
        'expected_response' => 'array',
        'field_mappings' => 'array',
        'transformers' => 'array',
        'last_used' => 'datetime',
        'last_error' => 'datetime',
        'error_count' => 'integer',
        'timeout' => 'integer',
        'rate_limit' => 'integer',
        'rate_window' => 'integer',
    ];

    protected $attributes = [
        'error_count' => 0,
        'timeout' => 30,
        'direct_access' => false,
        'status' => 'new',
    ];

    public function apiConnection(): BelongsTo
    {
        return $this->belongsTo(ApiConnection::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class, 'endpoint_id');
    }

    public function isEnabled(): bool
    {
        return $this->status === 'active';
    }
}
