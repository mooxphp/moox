<?php

declare(strict_types=1);

namespace Moox\Contact\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Moox\Contact\Models\CompanyContact;
use Moox\Contact\Models\Contact;

final class CompanyContactRelation
{
    /** @return BelongsToMany<Model, Contact, CompanyContact> */
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

    /** @return BelongsToMany<Model, Model, CompanyContact> */
    public static function forCompany(Model $company): BelongsToMany
    {
        return $company->belongsToMany(
            CompanyContactRelationConfig::inverseRelatedModel(),
            CompanyContactRelationConfig::pivotTable(),
            CompanyContactRelationConfig::companyForeignKey(),
            CompanyContactRelationConfig::contactForeignKey(),
        )
            ->using(CompanyContactRelationConfig::pivotModel())
            ->withPivot(CompanyContactRelationConfig::pivotColumns())
            ->withTimestamps();
    }
}
