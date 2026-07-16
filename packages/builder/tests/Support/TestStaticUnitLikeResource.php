<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Filament\Resources\Resource;

class TestStaticUnitLikeResource extends Resource
{
    protected static ?string $model = TestStaticUnitLike::class;
}
