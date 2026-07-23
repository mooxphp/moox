<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticChargeReason extends BaseStaticModel
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_charge_reasons';

    protected $fillable = [
        'code',
    ];
}
