<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class TestStaticUnitLike extends Model
{
    public $timestamps = false;

    protected $table = 'static_units';

    protected $guarded = [];
}
