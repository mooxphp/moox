<?php

declare(strict_types=1);

namespace Moox\Company\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Company\Database\Factories\CompanyFactory;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Core\Traits\MorphPivot\HasMorphPivotRelations;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

class Company extends BaseItemModel
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;
    use HasModelTaxonomy;
    use HasMorphPivotRelations;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'status',
        'name',
        'display_name',
        'legal_name',
        'note',
        'search_terms',
        'parent_id',
        'external_reference',
        'phone',
        'fax',
        'url',
        'email',
        'tax_number',
        'vat_number',
        'has_no_vat_number',
        'partner_type',
        'partner_id',
        'company_type',
        'default_currency_code',
        'is_fully_owned_subsidiary',
        'no_marketing_action',
        'no_marketing_action_reason',
        'language_id',
        'localization_id',
        'sort',
        'is_active',
        'approved_at',
        'approved_by_type',
        'approved_by_id',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_no_vat_number' => 'boolean',
            'partner_type' => 'integer',
            'partner_id' => 'integer',
            'is_fully_owned_subsidiary' => 'boolean',
            'no_marketing_action' => 'boolean',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'data' => 'array',
            'sort' => 'integer',
        ];
    }

    public static function getResourceName(): string
    {
        return 'company';
    }

    public static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Company, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function approvedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphToMany<Model, $this>
     */
    /**
     * @return MorphToMany<Model, $this>
     */
    public function addresses(): MorphToMany
    {
        return $this->morphPivotRelation('addressables');
    }

    /**
     * Primary related record ({@see addresses()} + config primary).
     *
     * @return MorphToMany<Model, $this>
     */
    public function address(): MorphToMany
    {
        return $this->primaryMorphPivotRelation('addressables');
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call($method, $parameters): mixed
    {
        $taxonomies = $this->getTaxonomyService()->getTaxonomies();

        if (array_key_exists($method, $taxonomies)) {
            return $this->taxonomy($method);
        }

        return $this->morphPivotCall($method, $parameters);
    }

    public function displayLabel(): string
    {
        return $this->display_name
            ?? $this->name
            ?? $this->legal_name
            ?? (string) $this->getKey();
    }

    protected static function booted(): void
    {
        static::saving(function (Company $company): void {
            if ($company->parent_id !== null && $company->parent_id === $company->getKey()) {
                $company->parent_id = null;
            }

            if ($company->default_currency_code !== null) {
                $company->default_currency_code = strtoupper(trim($company->default_currency_code));
            }
        });
    }
}
