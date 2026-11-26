<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\External\Models\HasExternal;

class ExternalModel extends Model
{
    use HasExternal;

    protected $table = 'external_models';

    protected $guarded = [];
}