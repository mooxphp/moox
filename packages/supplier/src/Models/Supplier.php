<?php

declare(strict_types=1);

namespace Moox\Supplier\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Data\Models\StaticLanguage;
use Moox\Supplier\Database\Factories\SupplierFactory;

/**
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany<Model, $this> companies()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<SupplierAssignment, $this> supplierAssignments()
 */
class Supplier extends BaseRecordModel
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    use HasModelTaxonomy;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'status',
        'supplier_number',
        'external_reference',
        'search_terms',
        'discount_percent',
        'lead_time_days',
        'minimum_order_value',
        'language_id',
        'is_preferred',
        'note',
        'sort',
        'is_active',
        'approved_at',
        'approved_by_type',
        'approved_by_id',
        'data',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'lead_time_days' => 'integer',
            'minimum_order_value' => 'decimal:2',
            'language_id' => 'integer',
            'is_preferred' => 'boolean',
            'sort' => 'integer',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public static function getResourceName(): string
    {
        return 'supplier';
    }

    public static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }

    /**
     * @return BelongsTo<StaticLanguage, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(StaticLanguage::class, 'language_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function approvedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function displayLabel(): string
    {
        if ($this->supplier_number) {
            return $this->supplier_number;
        }

        return (string) $this->getKey();
    }
}
