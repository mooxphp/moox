<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Moox\Contact\Support\CompanyContactRelationConfig;

class CompanyContact extends Pivot
{
    protected $table = 'company_contact';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'contact_id',
        'role',
        'is_primary',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /** @return BelongsTo<Model, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(
            CompanyContactRelationConfig::relatedModel(),
            CompanyContactRelationConfig::companyForeignKey(),
        );
    }

    /** @return BelongsTo<Model, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(
            CompanyContactRelationConfig::inverseRelatedModel(),
            CompanyContactRelationConfig::contactForeignKey(),
        );
    }
}
