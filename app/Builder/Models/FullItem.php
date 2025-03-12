<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class FullItem extends Model
{
    use BaseInModel;
    use SingleSimpleInModel;

    protected $table = 'preview_full_items';

    protected $fillable = [
        'simple',
        'title',
        'content',
        'tabs',
        'street',
        'city',
        'postal_code',
        'country',
        'status',
        'type',
    ];

    protected $casts = [
    ];
}
