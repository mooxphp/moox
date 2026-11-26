<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Publish\Models\HasPublish;

class ProductTranslation extends Model
{
    use HasPublish;
    use HasActive;
    protected $table = 'product_translations';

    protected $guarded = [];

    public $timestamps = false;
}