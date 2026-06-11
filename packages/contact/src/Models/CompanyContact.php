<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot row for company ↔ contact (see config contact.relations.companies / company.relations.contacts).
 * Sides are resolved dynamically on {@see Contact} / {@see \Moox\Company\Models\Company} via HasRelations — not here.
 */
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
}
