<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Support;

use Moox\Definition\Laravel\Model;
use Moox\Definition\Resolution\ResolvedEntity;

class ArticleRecord extends Model
{
    public static function definition(): ResolvedEntity
    {
        return ArticleCatalog::catalog()->resolve('moox/article', 'article');
    }

    /**
     * @return array<string, class-string<Model>>
     */
    protected static function modelBindings(): array
    {
        return [
            'productGroup' => ProductGroupRecord::class,
        ];
    }
}
