<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInModel;

class SoftItem extends Model
{
    use BaseInModel;
    use SingleSoftDeleteInModel;
    protected $table = 'preview_soft_items';

    protected $fillable = [
        'title',
        'content',
        'keks',
        'tabs',
        'street',
        'city',
        'postal_code',
        'country',
        'type',
        'status',
        'softDelete',
    ];

    protected $casts = [
    ];
}
