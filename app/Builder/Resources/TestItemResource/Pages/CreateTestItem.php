<?php

declare(strict_types=1);

namespace App\Builder\Resources\TestItemResource\Pages;

use App\Builder\Resources\TestItemResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateTestItem extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = TestItemResource::class;
}
