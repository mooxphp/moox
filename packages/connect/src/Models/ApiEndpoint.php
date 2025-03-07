<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class ApiEndpoint extends Model
{
    use BaseInModel, SingleSimpleInModel;

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
    ];

    protected $casts = [
        'variables' => 'array',
        'response_map' => 'array',
        'expected_response' => 'array',
        'field_mappings' => 'array',
        'transformers' => 'array',
    ];

    public function apiConnection(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ApiConnection::class);
    }
}
