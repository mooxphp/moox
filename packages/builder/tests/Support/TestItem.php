<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Concerns\InteractsWithCustomFields;

class TestItem extends Model
{
    use InteractsWithCustomFields;

    protected $table = 'items';

    protected $guarded = [];

    protected static function customFieldsEntity(): ?string
    {
        return 'item';
    }
}
