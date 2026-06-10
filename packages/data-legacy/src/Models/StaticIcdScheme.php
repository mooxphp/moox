<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticIcdScheme extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_icd_schemes';

    protected $fillable = [
        'code',
        'common_name',
        'description',
    ];
}
