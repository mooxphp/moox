<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Filament\Resources\Resource;

class TestCategoryLikeResource extends Resource
{
    protected static ?string $model = TestCategoryLike::class;

    protected static ?string $recordTitleAttribute = null;

    protected static function customFieldsEntity(): ?string
    {
        return 'category';
    }
}
