<?php

declare(strict_types=1);

namespace Moox\Supplier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property bool $is_primary
 * @property string|null $role
 */
class SupplierAssignment extends MorphPivot
{
    protected $table = 'supplier_assignments';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'supplier_id',
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
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
