<?php

declare(strict_types=1);

namespace Moox\Support\Tests\Fixtures;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Publish\Models\HasPublish;

class Product extends Model implements TranslatableContract
{
    
    use HasActive;
    use HasPublish;
    use Translatable;

    protected $table = 'products';

    protected $guarded = [];

    public array $translatedAttributes = [
        'title',
        'slug',
        'description',
    ];
}