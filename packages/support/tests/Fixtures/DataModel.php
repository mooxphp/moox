<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Data\Models\HasData;

class DataModel extends Model
{
    use HasData;

    protected $table = 'data_models';

    protected $guarded = [];
}