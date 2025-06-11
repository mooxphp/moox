<?php

declare(strict_types=1);

namespace Moox\Localization\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Data\Models\StaticLanguage;

/**
 * @property int $id
 * @property int $language_id
 * @property string $title
 * @property string $slug
 * @property int|null $fallback_language_id
 * @property bool $is_active_admin
 * @property bool $is_active_frontend
 * @property bool $is_default
 * @property string $fallback_behaviour
 * @property string $language_routing
 * @property string $routing_path
 * @property string $routing_subdomain
 * @property string $routing_domain
 * @property int $translation_status
 * @property array $language_settings
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StaticLanguage $language
 * @property-read self|null $fallbackLanguage
 */
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
