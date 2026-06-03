<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Item\BaseItemModel;

final class ApiLog extends BaseItemModel
{
    use HasFactory;
    use SoftDeletes;

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
