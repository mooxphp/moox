<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticUnit extends BaseStaticModel
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_units';

    protected $fillable = [
        'code',
        'symbol',
    ];
}
