<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticEasScheme extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_eas_schemes';

    protected $fillable = [
        'code',
        'common_name',
        'description',
    ];
}
