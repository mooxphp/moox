<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Filament\Resources\Resource;
use Moox\Builder\Concerns\HasCustomFields;

class TestItemResource extends Resource
{
    use HasCustomFields;

    protected static ?string $model = TestItem::class;
}
