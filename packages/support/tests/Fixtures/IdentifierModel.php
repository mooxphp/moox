<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Identity\Models\HasIdentifier;

class IdentifierModel extends Model
{
    use HasIdentifier;

    protected $table = 'identifier_models';

    protected $guarded = [];
}