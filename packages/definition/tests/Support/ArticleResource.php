<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Support;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Moox\Definition\Filament\Resource as DefinitionResource;

final class ArticleResource extends Resource
{
    protected static ?string $model = ArticleRecord::class;

    public static function form(Schema $schema): Schema
    {
        $projection = new DefinitionResource(ArticleCatalog::catalog()->resolve('moox/article', 'article'));
        $projection->field('article_number')->formComponent()->maxLength(16);

        return $projection->applyForm($schema);
    }
}
