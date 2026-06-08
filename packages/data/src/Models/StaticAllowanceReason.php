<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticAllowanceReason extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_allowance_reasons';

    protected $fillable = [
        'code',
        'common_name',
        'description',
    ];
}
