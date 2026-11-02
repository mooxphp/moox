<?php

declare(strict_types=1);

namespace Moox\Supplier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class Supplier extends Model
{
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use SoftDeletes;

    protected $table = 'suppliers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'supplier_type',
        'supplier_name',
        'supplier_number',
        'note',
        'external_reference',
        'external_info',
        'external_status',
        'contact_id',
        'language_id',
        'data',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
        'archived_at',
        'archived_by_id',
        'archived_by_type',
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
        return 'supplier';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'data' => 'array',
            'contact_id' => 'integer',
            'language_id' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'archived_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'archived_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }
}
