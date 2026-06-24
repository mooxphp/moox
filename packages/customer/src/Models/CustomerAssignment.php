<?php

declare(strict_types=1);

namespace Moox\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property bool $is_primary
 * @property string|null $role
 */
class CustomerAssignment extends MorphPivot
{
    protected $table = 'customer_assignments';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'customer_id',
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
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
