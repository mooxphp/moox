<?php

declare(strict_types=1);

namespace Moox\Department\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property bool $is_primary
 * @property string|null $role
 */
class Departmentable extends MorphPivot
{
    protected $table = 'departmentables';

    public $incrementing = true;

    protected $fillable = [
        'departmentable_type',
        'departmentable_id',
        'department_id',
        'is_primary',
        'role',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function departmentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
