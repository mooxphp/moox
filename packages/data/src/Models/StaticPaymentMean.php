<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;
class StaticPaymentMean extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_payment_means';

    protected $fillable = [
        'code',
        'common_name',
        'description',
    ];
}
