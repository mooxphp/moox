<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticVatExemptionReason extends BaseStaticModel
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_vat_exemption_reasons';

    protected $fillable = [
        'code',
        'vat_category_code',
    ];

    public function vatCategory(): BelongsTo
    {
        return $this->belongsTo(StaticVatCategory::class, 'vat_category_code', 'code');
    }
}
