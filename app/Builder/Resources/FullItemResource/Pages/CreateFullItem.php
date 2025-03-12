<?php

declare(strict_types=1);

namespace App\Builder\Resources\FullItemResource\Pages;

use App\Builder\Resources\FullItemResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateFullItem extends CreateRecord
{
    use BaseInCreatePage;
    use SingleSimpleInCreatePage;

    protected static string $resource = FullItemResource::class;
}
