<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Item\BaseItemModel;

class ApiImportRecord extends BaseItemModel
{
    use HasFactory;
    use SoftDeletes;

    private const IDENTITY_SCOPE_SALT = 'connect:global-scope:';

    protected $table = 'api_import_records';

    protected $fillable = [
        'api_connection_id',
        'api_endpoint_id',
        'external_key',
        'sync_scope_hash',
        'payload',
        'payload_hash',
        'sync_batch_id',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function apiConnection(): BelongsTo
    {
        return $this->belongsTo(ApiConnection::class);
    }

    public function apiEndpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class);
    }

    public static function resolveIdentityScopeHash(?string $scopeHash, ?string $externalKey): ?string
    {
        if (is_string($scopeHash) && $scopeHash !== '') {
            return $scopeHash;
        }

        if (is_string($externalKey) && $externalKey !== '') {
            return hash('sha256', self::IDENTITY_SCOPE_SALT.$externalKey);
        }

        return null;
    }
}
