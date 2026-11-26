<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\SoftDelete\Models\HasSoftDelete;

class SoftDeleteModel extends Model
{
    use HasSoftDelete;

    protected $table = 'soft_delete_models';

    protected $guarded = [];
}