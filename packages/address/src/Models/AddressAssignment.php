<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property bool $billing_address
 * @property bool $postal_address
 * @property bool $delivery_address
 */
class AddressAssignment extends MorphPivot
{
    protected $table = 'address_assignments';

    public $incrementing = true;

    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'address_id',
        'billing_address',
        'postal_address',
        'delivery_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_address' => 'boolean',
            'postal_address' => 'boolean',
            'delivery_address' => 'boolean',
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
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return list<string>
     */
    public function activeRoles(): array
    {
        return collect([
            'billing' => $this->billing_address,
            'postal' => $this->postal_address,
            'delivery' => $this->delivery_address,
        ])
            ->filter()
            ->keys()
            ->all();
    }
}
