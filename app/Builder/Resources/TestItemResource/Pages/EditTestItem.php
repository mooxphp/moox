<?php

declare(strict_types=1);

namespace App\Builder\Resources\TestItemResource\Pages;

use App\Builder\Resources\TestItemResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditTestItem extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = TestItemResource::class;
}
