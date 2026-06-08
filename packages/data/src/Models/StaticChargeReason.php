<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticChargeReason extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_charge_reasons';

    protected $fillable = [
        'code',
        'common_name',
        'description',
    ];
}
