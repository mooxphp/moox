<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddressUsageTypeTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'address_usage_type_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'title',
        'slug',
    ];

    /**
     * @return BelongsTo<AddressUsageType, $this>
     */
    public function addressUsageType(): BelongsTo
    {
        return $this->belongsTo(AddressUsageType::class);
    }
}
