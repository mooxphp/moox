<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Publish\Models\HasPublish;

class PublishModel extends Model
{
    use HasPublish;

    protected $table = 'publish_models';

    protected $guarded = [];
}