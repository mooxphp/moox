<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class RelationModel extends Model
{
    protected $table = 'relation_models';

    protected $guarded = [];

    public static function getResourceName(): string
    {
        return 'relation';
    }
}