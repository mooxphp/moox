<?php

declare(strict_types=1);

namespace Moox\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property bool $is_primary
 * @property string|null $role
 */
class StaffAssignment extends MorphPivot
{
    protected $table = 'staff_assignments';

    public $incrementing = true;

    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'staff_id',
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
    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
