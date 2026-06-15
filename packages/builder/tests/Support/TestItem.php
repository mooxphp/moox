<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class TestItem extends Model
{
    protected $table = 'items';

    protected $guarded = [];
}
