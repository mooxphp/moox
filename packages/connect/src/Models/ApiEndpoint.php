<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class ApiEndpoint extends Model
{
    use BaseInModel, SingleSimpleInModel, SoftDeletes;

    protected $table = 'api_endpoints';

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
        // optionale Import-spezifische Felder für den generischen Import-Core
        // vollqualifizierter Klassenname des Zielmodells (z.B. App\Models\Product)
        'destination_model',
        // JSON-Mapping externer Schlüssel → interne Spalten (z.B. {"external_id": "id"})
        'key_fields',
        // optionaler Name des Felds in der Payload, das als external_key in api_import_records gespeichert wird
        'external_key_field',
        // Sync-Verhalten (append|sync) + optionaler Prune-Scope
        'sync_mode',
        'sync_scope_fields',
        // Allgemeine endpoint-spezifische Optionen (json)
        'options',
        // Liste-→Detail-Orchestrierung
        'list_item_path',
        'list_id_key',
        'parent_endpoint_id',
        'route_param_key',
        'variable_key',
    ];

    protected $casts = [
        'variables' => 'array',
        'response_map' => 'array',
        'expected_response' => 'array',
        'field_mappings' => 'array',
        'transformers' => 'array',
        'key_fields' => 'array',
        'sync_scope_fields' => 'array',
        'options' => 'array',
    ];

    public function option(string $key, mixed $default = null): mixed
    {
        return data_get($this->options ?? [], $key, $default);
    }

    public function parentEndpoint(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_endpoint_id');
    }

    public function apiConnection(): BelongsTo
    {
        return $this->belongsTo(ApiConnection::class);
    }

    public function importRecords(): HasMany
    {
        return $this->hasMany(ApiImportRecord::class, 'api_endpoint_id');
    }
}
