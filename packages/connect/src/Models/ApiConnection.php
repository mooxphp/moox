<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class ApiConnection extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'api_connections';

    protected $fillable = [
        'name',
        'base_url',
        'api_type',
        'auth_type',
        'auth_credentials',
        'headers',
        'rate_limit',
        'lang_param',
        'default_locale',
        'status',
        'notify_on_failure',
    ];

    protected $casts = [
        'auth_credentials' => 'array',
        'headers' => 'array',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(ApiEndpoint::class);
    }
}
