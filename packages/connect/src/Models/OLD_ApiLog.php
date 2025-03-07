<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApiLog extends Model
{
    protected $fillable = [
        'api_connection_id',
        'endpoint_id',
        'trigger',  // CRON, USER, WEBHOOK, SYSTEM
        'request_data',
        'response_data',
        'status_code',
        'error_message',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function apiConnection(): BelongsTo
    {
        return $this->belongsTo(ApiConnection::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'endpoint_id');
    }
}
