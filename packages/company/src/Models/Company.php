<?php

declare(strict_types=1);

namespace Moox\Company\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Company\Database\Factories\CompanyFactory;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Data\Models\StaticLanguage;

/**
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo<Company, $this> parent()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany<Company, $this> children()
 * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany<Model, $this> contacts()
 * @method \Illuminate\Database\Eloquent\Relations\MorphToMany<Model, $this> addresses()
 * @method \Illuminate\Database\Eloquent\Relations\MorphToMany<Model, $this> address()
 * @method \Illuminate\Database\Eloquent\Relations\MorphToMany<Model, $this> staff()
 * @method \Illuminate\Database\Eloquent\Relations\MorphToMany<Model, $this> staffMember()
 */
class Company extends BaseRecordModel
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use HasModelTaxonomy;
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
        'parent_id',
        'external_reference',
        'phone',
        'fax',
        'url',
        'email',
        'tax_number',
        'vat_number',
        'default_currency_code',
        'language_id',
        'data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'language_id' => 'integer',
            'data' => 'array',
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
     * @return BelongsTo<StaticLanguage, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(StaticLanguage::class, 'language_id');
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

            if (is_string($company->default_currency_code) && trim($company->default_currency_code) !== '') {
                $company->default_currency_code = strtoupper(trim($company->default_currency_code));
            }
        });
    }
}
