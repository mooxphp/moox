<?php

declare(strict_types=1);

namespace Moox\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationTypeTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'organization_type_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'title',
        'slug',
    ];

    /**
     * @return BelongsTo<OrganizationType, $this>
     */
    public function organizationType(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class);
    }
}
