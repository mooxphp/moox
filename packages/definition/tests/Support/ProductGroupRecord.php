<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Support;

use Moox\Definition\Laravel\Model;
use Moox\Definition\Resolution\ResolvedEntity;

class ProductGroupRecord extends Model
{
    public static function definition(): ResolvedEntity
    {
        return ArticleCatalog::catalog()->resolve('moox/product-group', 'productGroup');
    }
}
