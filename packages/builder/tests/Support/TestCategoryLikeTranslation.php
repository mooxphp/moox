<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class TestCategoryLikeTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'category_translations';

    protected $guarded = [];
}
