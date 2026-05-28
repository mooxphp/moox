<?php

declare(strict_types=1);

namespace Moox\Contact\Support;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Moox\Company\Models\Company;
use Moox\Contact\Models\CompanyContact;
use Moox\Contact\Models\Contact;

final class CompanyContactRelation
{
    /** @return BelongsToMany<Company, Contact, CompanyContact> */
    public static function forContact(Contact $contact): BelongsToMany
    {
        return $contact->belongsToMany(
            CompanyContactRelationConfig::relatedModel(),
            CompanyContactRelationConfig::pivotTable(),
            CompanyContactRelationConfig::contactForeignKey(),
            CompanyContactRelationConfig::companyForeignKey(),
        )
            ->using(CompanyContactRelationConfig::pivotModel())
            ->withPivot(CompanyContactRelationConfig::pivotColumns())
            ->withTimestamps();
    }

    /** @return BelongsToMany<Contact, Company, CompanyContact> */
    public static function forCompany(Company $company): BelongsToMany
    {
        return $company->belongsToMany(
            Contact::class,
            CompanyContactRelationConfig::pivotTable(),
            CompanyContactRelationConfig::companyForeignKey(),
            CompanyContactRelationConfig::contactForeignKey(),
        )
            ->using(CompanyContactRelationConfig::pivotModel())
            ->withPivot(CompanyContactRelationConfig::pivotColumns())
            ->withTimestamps();
    }
}
