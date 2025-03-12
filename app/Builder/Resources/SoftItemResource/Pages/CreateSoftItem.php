<?php

declare(strict_types=1);

namespace App\Builder\Resources\SoftItemResource\Pages;

use App\Builder\Resources\SoftItemResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInCreatePage;

class CreateSoftItem extends CreateRecord
{
    use BaseInCreatePage;
    use SingleSoftDeleteInCreatePage;
    protected static string $resource = SoftItemResource::class;
}
