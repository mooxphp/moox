<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pivot row for assignable ↔ product (see config product.relations.product_assignments).
 *
 * @property bool $is_primary
 * @property int $sort_order
 */
class ProductAssignment extends MorphPivot
{
    protected $table = 'product_assignments';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'product_id',
        'is_primary',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
