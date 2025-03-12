<?php

declare(strict_types=1);

namespace App\Builder\Resources\FullItemResource\Pages;

use App\Builder\Resources\FullItemResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditFullItem extends EditRecord
{
    use BaseInEditPage;
    use SingleSimpleInEditPage;

    protected static string $resource = FullItemResource::class;
}
