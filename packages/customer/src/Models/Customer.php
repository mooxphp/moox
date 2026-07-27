<?php

declare(strict_types=1);

namespace Moox\Customer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Customer\Database\Factories\CustomerFactory;
use Moox\Data\Models\StaticLanguage;

/**
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany<Model, $this> companies()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<CustomerAssignment, $this> customerAssignments()
 */
class Customer extends BaseRecordModel
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    use HasModelTaxonomy;
    use HasUuids;

    protected $table = 'customers';

    protected $fillable = [
        'status',
        'customer_number',
        'external_reference',
        'customer_name',
        'search_terms',
        'price_type',
        'customer_group',
        'discount_percent',
        'credit_limit',
        'language_id',
        'note',
        'sort',
        'is_active',
        'approved_at',
        'approved_by_type',
        'approved_by_id',
        'data',
        // Needed so transform field_map can persist soft-deletes via mass assignment.
        'deleted_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'language_id' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'data' => 'array',
        ];
    }

    public static function getResourceName(): string
    {
        return 'customer';
    }

    public static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
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
        if (filled($this->customer_name)) {
            return (string) $this->customer_name;
        }

        if (filled($this->customer_number)) {
            return (string) $this->customer_number;
        }

        return (string) $this->getKey();
    }
}
