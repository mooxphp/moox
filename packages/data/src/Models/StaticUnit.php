<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticUnit extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_units';

    protected $fillable = [
        'code',
        'common_name',
        'symbol',
        'description',
    ];
}
