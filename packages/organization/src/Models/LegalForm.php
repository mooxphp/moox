<?php

declare(strict_types=1);

namespace Moox\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class LegalForm extends Model
{
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use SoftDeletes;

    protected $table = 'legal_forms';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'title',
        'slug',
        'country_id',
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
    ];

    public static function getResourceName(): string
    {
        return 'legal-form';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'country_id' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'restored_at' => 'datetime',
        ];
    }
}
