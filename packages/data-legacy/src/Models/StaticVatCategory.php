<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticVatCategory extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_vat_categories';

    protected $fillable = [
        'code',
        'common_name',
        'description',
    ];
}
