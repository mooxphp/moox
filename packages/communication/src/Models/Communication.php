<?php

declare(strict_types=1);

namespace Moox\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class Communication extends Model
{
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use SoftDeletes;

    protected $table = 'communications';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'value',
        'label',
        'is_primary',
        'is_verified',
        'verified_at',
        'note',
        'external_reference',
        'external_info',
        'external_status',
        'owner_id',
        'owner_type',
        'communication_type_id',
        'data',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_primary' => false,
        'is_verified' => false,
    ];

    public static function getResourceName(): string
    {
        return 'communication';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'data' => 'array',
            'owner_id' => 'integer',
            'communication_type_id' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'restored_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
