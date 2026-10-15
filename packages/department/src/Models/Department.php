<?php

declare(strict_types=1);

namespace Moox\Department\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Department\Database\Factories\DepartmentFactory;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasAudit;

    use HasFactory;
    use HasIdentifier;
    use HasRelations;
    use SoftDeletes;

    protected $table = 'departments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'title',
        'slug',
        'note',
        'external_reference',
        'external_info',
        'external_status',
        'contact_id',
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

    public static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }

    public static function getResourceName(): string
    {
        return 'department';
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
