<?php

declare(strict_types=1);

namespace Moox\Localization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Data\Models\StaticLanguage;

class Localization extends Model
{
    protected $fillable = [
        'language_id',
        'title',
        'slug',
        'fallback_language_id',
        'is_active_admin',
        'is_active_frontend',
        'is_default',
        'fallback_behaviour',
        'language_routing',
        'routing_path',
        'routing_subdomain',
        'routing_domain',
        'translation_status',
        'language_settings',
    ];

    protected $casts = [
        'is_active_admin' => 'boolean',
        'is_active_frontend' => 'boolean',
        'is_default' => 'boolean',
        'translation_status' => 'integer',
        'language_settings' => 'array',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(StaticLanguage::class, 'language_id');
    }

    public function fallbackLanguage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'fallback_language_id');
    }
}
