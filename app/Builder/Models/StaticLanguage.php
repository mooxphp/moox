<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticLanguage extends Model
{
    use BaseInModel;
    use SingleSimpleInModel;
    protected $table = 'preview_static_languages';

    protected $fillable = [
        'simple',
        'title',
        'content',
        'tabs',
    ];

    protected $casts = [
    ];
}
