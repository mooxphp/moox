<?php

declare(strict_types=1);

namespace App\Builder\Resources\SoftItemResource\Pages;

use App\Builder\Resources\SoftItemResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInEditPage;

class EditSoftItem extends EditRecord
{
    use BaseInEditPage;
    use SingleSoftDeleteInEditPage;
    protected static string $resource = SoftItemResource::class;
}
