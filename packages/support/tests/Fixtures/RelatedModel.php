<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class RelatedModel extends Model
{
    protected $table = 'related_models';

    protected $guarded = [];
}