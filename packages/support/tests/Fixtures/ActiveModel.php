<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Active\Models\HasActive;

class ActiveModel extends Model
{
    use HasActive;

    protected $table = 'active_models';

    protected $guarded = [];
}