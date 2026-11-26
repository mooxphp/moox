<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class MorphOwnerModel extends Model
{
    protected $table = 'morph_owner_models';

    protected $guarded = [];
}