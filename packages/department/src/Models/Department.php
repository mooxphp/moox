<?php

declare(strict_types=1);

namespace Moox\Department\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Department\Database\Factories\DepartmentFactory;

/**
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<Departmentable, $this> departmentables()
 */
class Department extends BaseRecordModel
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    use HasModelTaxonomy;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'departments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'status',
        'name',
        'code',
        'description',
        'is_active',
        'external_reference',
        'data',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'data' => 'array',
        ];
    }

    public static function getResourceName(): string
    {
        return 'department';
    }

    public static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }

    public function displayLabel(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->code) {
            return $this->code;
        }

        return (string) $this->getKey();
    }
}
